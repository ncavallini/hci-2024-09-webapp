<nav class="navbar bg-body-tertiary ">
  <div class="container-fluid">
   <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <button class="navbar-toggler" onclick="javascript:window.history.go(-1)"><i class="fa-solid fa-arrow-left"></i></button>
    <a class="navbar-toggler" href="index.php?page=dashboard"><i class="fa-solid fa-house"></i></a>
    <!--<a class="navbar-brand" href="#">Visualizing Mental Load</a>-->
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Visualizing Mental Load</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">

        <li class="nav-item">
          <span class="badge rounded-pill bg-warning"><i class="fa fa-coins"></i> <?php echo $user['coins'] ?></span>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="index.php?page=dashboard">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php?page=visualize">Visualize</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php?page=manage">Manage</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Dropdown
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="#">Action</a></li>
              <li><a class="dropdown-item" href="#">Another action</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="#">Something else here</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<br>