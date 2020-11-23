<?php
require 'vendor/autoload.php';

$app = new \Slim\App([

'settings' => ['displayErrorDetails' => false, ]]);

function Redirect($url, $permanent = false)
{
    if (headers_sent() === false)
    {
        header('Location: ' . $url, true, ($permanent === true) ? 301 : 302);
    }

    exit();
}

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container)
{
    $view = new \Slim\Views\Twig('view', ['cache' => false]);

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

// Home Page function
$app->get('/', function ($request, $response, $args)
{
    session_start();
    if (isset($_SESSION['username']))
    {
        return $this
            ->view
            ->render($response, 'home.twig', ['username' => $_SESSION['username'], ]);
    }
    else $lg_code = '0';
    return $this
        ->view
        ->render($response, 'home.twig', ['username' => $lg_code, ]);
})->setName('home');

// Problem by Tag
$app->get('/problembytag', function ($request, $response)
{
    $connect = new mysqli("localhost", "root", "", "demo") or die("ERROR:could not connect to the database!!!");
    $sql = "Select * from tags  order by tag_name";
    $sql1 = "Select * from tags where type = 'author' order by tag_name";
    $sql2 = "Select * from tags where type = 'actual_tag' order by tag_name";

    $result = $connect->query($sql);
    $result1 = $connect->query($sql1);
    $result2 = $connect->query($sql2);

    $op = $result->fetch_all();
    $op1 = $result1->fetch_all();
    $op2 = $result2->fetch_all();

    //$op = json_encode($op);
    session_start();
    if (isset($_SESSION['username']))
    {
        $user_sql = "Select *  from user_tags group by tag having user = '" . $_SESSION['username'] . "'";
        $cnt = "select user, tag ,COUNT(*) from user_tags GROUP BY tag , user having user = '" . $_SESSION['username'] . "'";
        $res = $connect->query($user_sql);
        $res = $res->fetch_all();
        $counter = $connect->query($cnt);
        $counter = $counter->fetch_all();
        return $this
            ->view
            ->render($response, 'index.twig', ['key' => $op, 'auth' => $op1, 'atag' => $op2, 'username' => $_SESSION['username'], 'u_tags' => $res, 'cnt' => $counter

        ]);
    }
    else
    {
        $lg_code = '0';
        return $this
            ->view
            ->render($response, 'index.twig', ['key' => $op, 'auth' => $op1, 'atag' => $op2, 'username' => $lg_code

        ]);
    }

})->setName('tag');

$app->get('/login', function ($request, $response)
{

    return $this
        ->view
        ->render($response, 'login.twig', ['username' => '0']);

});

//post method Login
$app->post('/login', function ($request, $response)
{

    $actual_link = "http://$_SERVER[HTTP_HOST]";
    $basePath = $request->getUri()
        ->getBasePath();
    $path = $actual_link . $basePath;
    if (isset($_POST['login-username']))
    {
        $connect = new mysqli("localhost", "root", "", "demo") or die("ERROR:could not connect to the database!!!");
        $sql = "Select (password) from users where username = '" . $_POST['login-username'] . "'";
        $result = $connect->query($sql);

        if ($result->num_rows > 0)
        {
            $result = $result->fetch_assoc();
            //var_dump($result);
            if ($result['password'] === $_POST['login-pwd'])
            {
                session_start();
                $_SESSION['username'] = $_POST['login-username'];
            }
            else
            {
                echo "Incorrect pwd";
            }
        }
        else
        {
            echo "No such User Found";
        }

        if (isset($_SESSION['username']))
        {
            $link = $path . "/";

            Redirect($link, false);
        }
        else
        {
            $link = $path . "/login";
            Redirect($link, false);
        }
    }
})->setName('login');

$app->get('/register', function ($request, $response)
{

    return $this
        ->view
        ->render($response, 'register.twig', ['username' => '0']);

})->setName('reg');

$app->post('/register', function ($request, $response)
{
    $actual_link = "http://$_SERVER[HTTP_HOST]";
    $basePath = $request->getUri()
        ->getBasePath();
    $path = $actual_link . $basePath;

    if (isset($_POST['reg-username']))
    {
        $connect = new mysqli("localhost", "root", "", "demo") or die("ERROR:could not connect to the database!!!");
        $sql1 = "Select * from users where username = '" . $_POST['reg-username'] . "'";
        $result = $connect->query($sql1);
        if ($result->num_rows > 0)
        {
            $link = $path . "/register";
            Redirect($link, false);

        }
        else
        {
            $sql = "Insert into users (name,username,email,password)values ('" . $_POST['reg-name'] . "','" . $_POST['reg-username'] . "','" . $_POST['reg-email'] . "','" . $_POST['reg-pwd'] . "')";
            if ($connect->query($sql) === true)
            {
                $link = $path . "/login";
                Redirect($link, false);
                // echo "created<br>";
                
            }
            else
            {
                echo "Error: " . $sql . "<br>" . $connect->error;
                $link = $path . "/register";
                Redirect($link, false);

            }
        }

    }
})->setName('register');

$app->get('/tags/{tagname}', function ($request, $response, $args)
{
    $tag_req = $args["tagname"];
    session_start();
    $arr = explode(',', $tag_req);

    $ct = count($arr);
    $search = "";
    //echo $search;
    for ($i = 0;$i < $ct;$i++)
    {
        if ($i != $ct - 1) $search = $search . "'" . trim($arr[$i]) . "',";
        else $search = $search . "'" . trim($arr[$i]) . "'";
        /* echo $search . '<br />';*/
    }

    $connect = new mysqli("localhost", "root", "", "demo") or die("ERROR:could not connect to the database!!!");
    $sql = "Select * from questions where tag in (" . $search . ")";

    $result = $connect->query($sql);

    $op = $result->fetch_all();

    if (isset($_SESSION['username']))
    {
        $sql1 = "Select * from user_tags where user  = '" . $_SESSION['username'] . "'";
        $res = $connect->query($sql1);
        $res = $res->fetch_all();

        $sql2 = "Select * from user_tags where tag in (" . $search . ")and user ='" . $_SESSION['username'] . "'";
        $res2 = $connect->query($sql2);
        $res2 = $res2->fetch_all();
        return $this
            ->view
            ->render($response, 'tags.twig', ['tag_q' => $op, 'tag' => $tag_req, 'userq' => $res2,

        'username' => $_SESSION['username'], 'user_tags' => $res,

        ]);
    }
    else
    {
        $lg_code = '0';
        return $this
            ->view
            ->render($response, 'tags.twig', ['tag_q' => $op, 'tag' => $tag_req, 'username' => $lg_code

        ]);
    }

});

$app->post('/logout', function ($request, $response)
{
    $actual_link = "http://$_SERVER[HTTP_HOST]";
    $basePath = $request->getUri()
        ->getBasePath();
    $path = $actual_link . $basePath;
    session_start();
    unset($_SESSION["username"]);
    $link = $path . "/";
    Redirect($link, false);
})->setName('logout');

$app->post('/newtag', function ($request, $response)
{
    $actual_link = "http://$_SERVER[HTTP_HOST]";
    $basePath = $request->getUri()
        ->getBasePath();
    $path = $actual_link . $basePath;

    if (isset($_POST['tag_name']) && $_POST['tag_name'] != "")
    {
        $connect = new mysqli("localhost", "root", "", "demo") or die("ERROR:could not connect to the database!!!");
        $sql = "Insert into user_tags (user,qcode,tag)values ('" . $_POST['user'] . "','" . $_POST['qcode'] . "','" . $_POST['tag_name'] . "')";
        if ($connect->query($sql) === true)
        {
            $link = $path . "/tags/" . $_POST['url'];
            Redirect($link, false);
        }
        else
        {
            echo "Error: " . $sql . "<br>" . $connect->error;
            $link = $path . "/tags/" . $_POST['url'];
            Redirect($link, false);

        }

    }
    else
    {
        $link = $path . "/tags/" . $_POST['url'];
        Redirect($link, false);

    }

})->setName('newtag');



$app->run();

