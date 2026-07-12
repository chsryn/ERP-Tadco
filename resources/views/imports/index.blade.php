<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Data ERP TADCO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-5">
    <h3 class="mb-4">Import Data ERP TADCO</h3>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            Import File Master TADCO
        </div>

        <div class="card-body">
            <form action="{{ route('imports.master') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">File Excel Master</label>
                    <input type="file" name="file_master" class="form-control" required>

                    <small class="text-muted">
                        Upload file LP CUSTOMER yang memiliki sheet LIST CUSTOMER dan LIST ITEM.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">
                    Import Customer dan Item
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
