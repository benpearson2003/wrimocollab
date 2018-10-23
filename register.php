<?php include "base.php"; ?>
<!DOCTYPE html>
<html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<title>Cacoph Registration</title>
</head>
<body>
<?php
if(!empty($_POST['username']) && !empty($_POST['password']))
{
    $password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_DEFAULT);
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);

    $query = "SELECT * FROM players WHERE Username = '".$username."'";
    $checkusername = $pdo->prepare($query);
    $checkusername->execute();

    if($checkusername->rowCount() == 1)
    {
        echo "<h1>Error</h1>";
        echo "<p>Sorry, that username is taken. Please go back and try again.</p>";
    }
    else
    {
        $query = "INSERT INTO players (username,password,emailaddress,currency,spearfaes,archers,lights,heavies,wingfae,fwmoves,flmoves,mwmoves,mlmoves)
                        VALUES('".$username."', '".$password."', '".$email."',1000,2,2,2,2,0,2,2,4,3)";
        $registerquery = $pdo->prepare($query);
        $registerquery->execute();
        if($registerquery)
        {
            echo "<h1>Success</h1>";
            echo "<p>Your account was successfully created. Please <a href=\"index.php\">click here to login</a>.</p>";
        }
        else
        {
            echo "<h1>Error</h1>";
            echo "<p>Sorry, your registration failed. Please go back and try again.</p>";
        }
     }
}
else
{
    ?>
    <style>
    body {
        background-color: #f9f9d1;
        margin-left: auto;
        margin-right: auto;
    }
    form {
        width: 100vw;
        margin: 20px;
        margin-left: auto;
        margin-right: auto;
    }
    h1 {
        font-size: 4em;
        text-align: center;
    }
    form div {
        margin-top: 10px;
        margin-bottom: 10px;
    }
    input {
        width: 100vw;
        font-size: 3em;
        height: 3em;
    }
    form a {
        text-decoration: none;
    }
    </style>

   <h1>Register</h1>

    <form method="post" action="register.php" name="registerform" id="registerform">
        <div><input type="text" name="username" id="username" placeholder="Username"/></div>
        <div><input type="password" name="password" id="password" placeholder="Password"/></div>
        <div><input type="text" name="email" id="email" placeholder="Email"/></div>
        <input type="submit" name="register" id="register" value="Register" />
    </form>

    <?php
}
?>
</body>
</html>
