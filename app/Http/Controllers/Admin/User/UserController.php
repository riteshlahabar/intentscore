<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Common\UploadService;
use App\Services\ImportExport\CsvService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly CsvService $csv,
        private readonly UploadService $upload,
    ) {
    }

    public function index(Request $request): View
    {
        $users = $this->filteredQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User,
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['password'] = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ])['password'];

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->upload->store($request->file('photo'), 'users');
        }

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->assignableRoles($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateData($request, $user);

        if ($user->id === auth()->id() && ($data['status'] ?? 'active') !== 'active') {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        if ($request->filled('password')) {
            $request->validate(['password' => ['string', 'min:8', 'max:255']]);
            $data['password'] = $request->string('password')->toString();
        }

        if ($request->hasFile('photo')) {
            $this->upload->delete($user->photo);
            $data['photo'] = $this->upload->store($request->file('photo'), 'users');
        }

        $user->update($data);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot delete your own account.');

        $this->upload->delete($user->photo);
        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)->get()->map(fn (User $user) => [
            $user->name,
            $user->email,
            $user->phone,
            $user->role,
            $user->status,
        ]);

        return $this->csv->download(
            'users-'.now()->format('Ymd-His').'.csv',
            ['name', 'email', 'phone', 'role', 'status'],
            $rows,
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $count = 0;

        foreach ($this->csv->read($request->file('file')) as $row) {
            if (empty($row['email']) || empty($row['name'])) {
                continue;
            }

            $email = mb_substr(trim((string) $row['email']), 0, 180);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $user = User::firstOrNew(['email' => $email]);

            $requestedRole = $row['role'] ?? 'salesperson';
            $role = in_array($requestedRole, $this->assignableRoles($user), true)
                ? $requestedRole
                : 'salesperson';

            $status = $user->exists && in_array($row['status'] ?? $user->status, ['active', 'inactive'], true)
                ? ($row['status'] ?? $user->status)
                : 'inactive';

            $user->fill([
                'name' => mb_substr($row['name'], 0, 180),
                'phone' => isset($row['phone']) ? mb_substr($row['phone'], 0, 30) : null,
                'role' => $role,
                'status' => $status,
            ]);

            if (! $user->exists) {
                // Newly imported accounts remain inactive until an admin sets a password manually.
                $user->password = Str::random(40);
            }

            $user->save();
            $count++;
        }

        return back()->with(
            'success',
            "Imported {$count} users. Set a password manually before enabling newly imported accounts."
        );
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = User::query();

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"));
        }

        foreach (['role', 'status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return $query;
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => [
                'required',
                'email',
                'max:180',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in($this->assignableRoles($user))],
            'status' => ['required', 'in:active,inactive'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
    }

    private function assignableRoles(?User $user = null): array
    {
        return ['admin', 'salesperson'];
    }
}
