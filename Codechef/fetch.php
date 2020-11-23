<?php
//fetch.php;
session_start();

$data = array();

if (isset($_GET["query"]))
{
    $connect = new PDO("mysql:host=localhost; dbname=demo", "root", "");

    $query = "
 SELECT tag_name FROM tags 
 WHERE tag_name LIKE '" . $_GET["query"] . "%' 
 ORDER BY tag_name ASC 
 LIMIT 15
 ";

    $statement = $connect->prepare($query);

    $statement->execute();

    while ($row = $statement->fetch(PDO::FETCH_ASSOC))
    {
        $data[] = $row["tag_name"];
    }

    if (isset($_SESSION['username']))
    {
       $q1 = "Select *  from user_tags group by tag having user = '" . $_SESSION['username'] . "' and tag LIKE '" . $_GET["query"] . "%' order by tag ";
        $stmt = $connect->prepare($q1);

        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $data[] = $row["tag"];
        }

    }
}

echo json_encode($data);

?>

