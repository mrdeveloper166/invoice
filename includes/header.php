<?php
// session_start();

// if(!isset($_SESSION['admin'])){
// header("Location: login.php");
// exit;
// }
?>
<?php
session_start();
require_once __DIR__."/../config/database.php";

$company = mysqli_fetch_assoc(
mysqli_query($conn,"SELECT company_name, logo FROM company_settings LIMIT 1")
);
if(!$company){
  $company = ['company_name' => 'Invoice System', 'logo' => ''];
}

$users = mysqli_fetch_assoc(
mysqli_query($conn,"SELECT name FROM users LIMIT 1")
);

if(!isset($_SESSION['admin'])){
header("Location: login.php");
exit;
}
?>
<!DOCTYPE html>
<html>
<head>

<title>Invoice System</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
html,body{
height:100%;
}

body{
display:flex;
flex-direction:column;
min-height:100vh;
background:#f4f6f9;
}

.content{
flex:1;
margin-left:250px;
padding:20px;
transition:0.3s;
}

.content.expanded{
margin-left:70px;
}

.footer{
background:#234999;
color:white;
text-align:center;
padding:12px;
}


.app-sidebar{
width:250px;
height:100vh;
background:#234999;
position:fixed;
color:white;
left:0;
top:0;
transition: transform .25s ease;
z-index: 1040;
overflow-y:auto;
}

.app-sidebar a{
display:block;
color:white;
padding:12px 20px;
text-decoration:none;
}

.app-sidebar a:hover{
background:#2A95D0;
}

.content{
margin-left:250px;
padding:20px;
}

.topbar{
background:white;
padding:15px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
margin-bottom:20px;
}

.logo{
font-weight:bold;
font-size:20px;
}

/* Responsive sidebar */
.sidebar-toggle{
position:fixed;
left:14px;
top:14px;
z-index:1050;
border-radius:12px;
box-shadow:0 8px 18px rgba(0,0,0,0.12);
}
.sidebar-toggle.is-hidden{
display:none !important;
}
.sidebar-backdrop{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,0.35);
z-index:1035;
}
.sidebar-backdrop.show{ display:block; }

@media (max-width: 991.98px){
  .content{
    margin-left:0 !important;
  }
  .app-sidebar{
    transform: translateX(-100%);
  }
  .app-sidebar.show{
    transform: translateX(0);
  }
}

@media (min-width: 992px){
  /* Hide hamburger on desktop */
  .sidebar-toggle{ display:none; }
  /* Ensure sidebar stays visible on desktop */
  .app-sidebar{ transform:none !important; }
}

</style>

</head>

<body>
<button type="button" class="btn btn-primary sidebar-toggle" id="sidebarToggle" aria-controls="appSidebar" aria-label="Open menu">
  <i class="fa fa-bars"></i>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>