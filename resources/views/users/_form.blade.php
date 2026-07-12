@csrf

<div class="row">

    <div class="col-md-6 mb-3">
        <label class="form-label">Nama</label>

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
        <label class="form-label">Email</label>

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

<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Password

            @isset($user)
                <small class="text-muted">(Kosongkan jika tidak diganti)</small>
            @endisset

        </label>

        <input
            type="password"
            name="password"
            class="form-control @error('password') is-invalid @enderror">

        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Konfirmasi Password
        </label>

        <input
            type="password"
            name="password_confirmation"
            class="form-control">

    </div>

</div>

<div class="mb-3">

    <label class="form-label">
        Role
    </label>

    <select
        name="role"
        class="form-select @error('role') is-invalid @enderror"
        required>

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

<button class="btn btn-primary">
    Simpan
</button>

<a href="{{ route('users.index') }}" class="btn btn-secondary">
    Kembali
</a>
