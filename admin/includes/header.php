<!DOCTYPE html>
<html lang="en">
    <style>
  #main-content {
    margin-left: 15rem;
    transition: margin-left 0.3s ease;
  }

  #main-content.collapsed {
    margin-left: 4rem; /* match collapsed sidebar width */
  }
</style>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pageTitle ?? 'Library Dashboard' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex">
