<div class="card-body">
    <h5 class="mb-3">Informasi Akun</h5>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input
                type="text"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $user->name ?? '') }}"
                required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input
                type="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $user->email ?? '') }}"
                required>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <hr>
    <h5 class="mb-3">Keamanan</h5>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">
                Password
                @isset($user)
                    <small class="text-muted fw-normal">(Kosongkan jika tidak ingin diganti)</small>
                @endisset
                @empty($user)
                    <span class="text-danger">*</span>
                @endempty
            </label>
            <input
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                {{ isset($user) ? '' : 'required' }}>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">
                Konfirmasi Password
                @empty($user)
                    <span class="text-danger">*</span>
                @endempty
            </label>
            <input
                type="password"
                name="password_confirmation"
                class="form-control"
                {{ isset($user) ? '' : 'required' }}>
        </div>
    </div>

    <hr>
    <h5 class="mb-3">Akses & Status</h5>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                <option value="">-- Pilih Role --</option>
                @foreach($roles as $role)
                    <option
                        value="{{ $role->name }}"
                        {{ old('role', $selectedRole ?? '') == $role->name ? 'selected' : '' }}>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Status Akun <span class="text-danger">*</span></label>
            <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                <option value="1" {{ old('is_active', $user->is_active ?? 1) == 1 ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ old('is_active', $user->is_active ?? '') === 0 ? 'selected' : '' }}>Non-Aktif</option>
            </select>
            @error('is_active')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="card-footer d-flex justify-content-between bg-white">
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal & Kembali</a>
    <button type="submit" class="btn btn-primary">Simpan Data User</button>
</div>
