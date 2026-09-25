<?php
session_start();
include "config/database.php";

if(isset($_POST['login'])){

$email = $_POST['email'];
$password = md5($_POST['password']);

$query = mysqli_query($conn,"SELECT * FROM users 
WHERE email='$email' AND password='$password'");

if(mysqli_num_rows($query)>0){

$_SESSION['admin']=$email;

header("Location: index.php");
exit;

}else{

$error="Invalid Login Credentials";

}

}
?>

<!DOCTYPE html>
<html>
<head>

<title>Login - Invoice System</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
height:100vh;
display:flex;
align-items:center;
justify-content:center;
background:linear-gradient(135deg,#234999,#2A95D0);
}

.login-box{
width:400px;
background:white;
padding:35px;
border-radius:10px;
box-shadow:0 10px 30px rgba(0,0,0,0.2);
}

.login-title{
text-align:center;
font-weight:bold;
color:#234999;
margin-bottom:25px;
}

.btn-theme{
background:#234999;
color:white;
}

.btn-theme:hover{
background:#2A95D0;
color:white;
}

</style>

</head>

<body>

<div class="login-box">

<h3 class="login-title">Invoice System Login</h3>

<?php if(isset($error)){ ?>

<div class="alert alert-danger">
<?php echo $error; ?>
</div>

<?php } ?>

<?php if(isset($_GET['timeout'])){ ?>

<div class="alert alert-warning">
Session expired due to 5 minutes of inactivity. Please login again.
</div>

<?php } ?>

<form method="POST">

<div class="mb-3">

<label>Email</label>

<input type="email" name="email" class="form-control" required>

</div>

<div class="mb-3">

<label>Password</label>

<input type="password" name="password" class="form-control" required>

</div>

<button name="login" class="btn btn-theme w-100">
Login
</button>

</form>

</div>

</body>
</html>