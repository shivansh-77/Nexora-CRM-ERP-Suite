<?php
include('connection.php');

if(isset($_POST['query'])){
    $query = mysqli_real_escape_string($connection, $_POST['query']);
    $sql = "SELECT id, company_name, contact_person, mobile_no FROM contact
            WHERE company_name LIKE '%$query%' OR contact_person LIKE '%$query%' LIMIT 10";
    $result = mysqli_query($connection, $sql);

    if(mysqli_num_rows($result) > 0){
        echo '<ul style="list-style:none; margin:0; padding:0;">';
        while($row = mysqli_fetch_assoc($result)){
            echo '<li class="project-item" style="padding:5px; cursor:pointer;" data-id="'.$row['id'].'" data-contact="'.$row['mobile_no'].'">'
                 .htmlspecialchars($row['company_name'].' - '.$row['contact_person']).'</li>';
        }
        echo '</ul>';
    } else {
        echo '<li style="padding:5px;">No results found</li>';
    }
}
?>
