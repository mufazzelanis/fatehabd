<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\Thana;
use App\Models\Union;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Validation + document storage shared by every place a dealer account is created or
 * edited: public registration, the admin panel, and a thana dealer adding union dealers.
 * The photo is public (shown in the panels); NID and bank slip stay on the private disk.
 */
class DealerService
{
    public const DOCUMENTS = ['nid_front', 'nid_back', 'bank_slip'];

    public function rules(bool $requireDocuments, ?Dealer $dealer = null, bool $requirePassword = true): array
    {
        $doc = $requireDocuments ? 'required' : 'nullable';

        return [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/', 'unique:dealers,phone' . ($dealer ? ',' . $dealer->id : '')],
            'email' => 'nullable|email|max:255',
            'password' => ($requirePassword ? 'required' : 'nullable') . '|string|min:6|confirmed',
            'address' => 'required|string|max:1000',
            'district_id' => 'required|exists:districts,id',
            'thana_id' => 'required|exists:thanas,id',
            'union_id' => 'nullable|exists:unions,id',
            'nid_number' => 'required|string|max:50',
            'photo' => "{$doc}|image|max:4096",
            'nid_front' => "{$doc}|image|max:4096",
            'nid_back' => 'nullable|image|max:4096',
            'bank_slip' => "{$doc}|file|mimes:jpg,jpeg,png,webp,pdf|max:4096",
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
            'phone.unique' => 'A business account with this phone number already exists.',
        ];
    }

    /** Validates the request (phone normalised first) and returns the dealer attributes, files stored. */
    public function validateAndStore(Request $request, bool $requireDocuments, ?Dealer $dealer = null, bool $requirePassword = true): array
    {
        $request->merge(['phone' => Dealer::normalizePhone($request->input('phone'))]);
        $request->validate($this->rules($requireDocuments, $dealer, $requirePassword), $this->messages());
        $this->ensureLocationChain($request);

        $data = $request->only(['name', 'phone', 'email', 'address', 'district_id', 'thana_id', 'union_id', 'nid_number']);
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = ImageOptimizer::store($request->file('photo'), 'dealers/photos', 'public', 600);
            $this->deleteOld($dealer?->photo, 'public');
        }

        foreach (self::DOCUMENTS as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $data[$field] = str_starts_with((string) $file->getMimeType(), 'image/')
                    ? ImageOptimizer::store($file, 'dealer-documents', 'private', 1800)
                    : $file->store('dealer-documents', 'private');
                $this->deleteOld($dealer?->$field, 'private');
            }
        }

        return $data;
    }

    public function deleteFiles(Dealer $dealer): void
    {
        $this->deleteOld($dealer->photo, 'public');
        foreach (self::DOCUMENTS as $field) {
            $this->deleteOld($dealer->$field, 'private');
        }
    }

    /** Thana must belong to the district, and the union (if any) to the thana. */
    private function ensureLocationChain(Request $request): void
    {
        $thanaOk = Thana::whereKey($request->thana_id)->where('district_id', $request->district_id)->exists();
        if (! $thanaOk) {
            throw ValidationException::withMessages(['thana_id' => 'The selected thana is not in the selected district.']);
        }

        if ($request->filled('union_id') && ! Union::whereKey($request->union_id)->where('thana_id', $request->thana_id)->exists()) {
            throw ValidationException::withMessages(['union_id' => 'The selected union is not in the selected thana.']);
        }
    }

    private function deleteOld(?string $path, string $disk): void
    {
        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }
}
