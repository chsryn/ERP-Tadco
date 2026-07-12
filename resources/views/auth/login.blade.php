<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - ERP TADCO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="min-vh-100 d-flex align-items-center justify-content-center px-3">
        <div class="card shadow-sm border-0" style="width: 100%; max-width: 420px;">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Login</h1>
                <p class="text-muted mb-4">Masuk ke ERP TADCO</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email"
                            class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                            required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password"
                            class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input" value="1">
                        <label for="remember" class="form-check-label">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>

                <div class="text-center mt-3">
                    <span class="text-muted">Belum punya akun?</span>
                    <a href="{{ route('register') }}">Register</a>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
