<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'eVoting System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e74c3c;
            --secondary-color: #f8f9fc;
            --accent-color: #e74c3c;
            --text-dark: #5a5c69;
            --text-light: #ffffff;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top flex-md-nowrap p-0 shadow" style="
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        min-height: 4.5rem;
    ">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 d-flex align-items-center" href="<?= isAdmin() ? '#' : '../index' ?>" style="
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s;
        ">
            <i class="fas fa-vote-yea me-2" style="font-size: 1.5rem;"></i>eVoting System
        </a>
        
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" style="
            right: 1rem;
            top: 1.25rem;
            border: none;
            outline: none;
        ">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        
    </header>

    <style>
        @media (max-width: 767.98px) {
            .navbar-brand {
                font-size: 1rem !important;
                padding-left: 0.5rem !important;
            }
            .navbar-toggler {
                padding: 0.25rem 0.5rem !important;
            }
            .nav-link {
                font-size: 0.75rem !important;
                padding: 0.25rem 0.5rem !important;
            }
        }
        
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            transform: translateY(-1px);
        }
        
        .navbar-brand:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }
    </style>
</body>
</html>