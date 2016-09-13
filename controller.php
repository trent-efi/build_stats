<?php
//echo "PHP VERSION: ".phpversion();
/******************************************************************************
 *
 *****************************************************************************/
if (isset($_POST['function']) && !empty($_POST['function'])) {

    $action = $_POST['function'];

    switch($action) {
        //case 'get_data': $start = $_POST['start']; $end = $_POST['end']; $os = $_POST['os']; echo get_data($start, $end, $os); break;
        case 'get_data': 
            $start = $_POST['start']; 
            $end = $_POST['end']; 
            $os = $_POST['os'];
            $weekly_view = $_POST['weekly_view'];
            echo get_data($start, $end, $os, $weekly_view); 
            break;
        case 'update_graph':
            $start = $_POST['start']; 
            $end = $_POST['end']; 
            $os = $_POST['os'];
            $cb = $_POST['cb'];
            $weekly_view = $_POST['weekly_view'];
            $date_arr = $_POST['date_arr'];
            echo update_graph($start, $end, $os, $cb, $date_arr, $weekly_view);
            break;
        default: 
            echo "ERROR: Unknown action variable"; 
            break;
    }

} else {
    echo 'Please ensure you have entered your details';
}

///////////////////////////////////////////////////////////////////////////////


/******************************************************************************
 *
 *****************************************************************************/
function update_graph($start, $end, $os, $cb, $date_arr, $weekly_view){
    $data = "";
    
    if( $weekly_view == 1 ){
        $FC = update_weekly_graph($start, $end, $os, $cb, $date_arr, 1); 
        $VC_FC = update_weekly_graph($start, $end, $os, $cb, $date_arr, 2); 
        $IDC = update_weekly_graph($start, $end, $os, $cb, $date_arr, 3); 
        $VC_IDC = update_weekly_graph($start, $end, $os, $cb, $date_arr, 4); 
        
        $data = "[".$FC.",".$VC_FC.",".$IDC.",".$VC_IDC."]";  
    } else {
        $data = update_daily_graph($start, $end, $os, $cb, $date_arr);
    }

    return $data;
}

/******************************************************************************
 *
 *****************************************************************************/
function update_daily_graph($start, $end, $os, $cb, $date_arr){
    $servername = "codesearch";
    $username = "efi_devops";
    $password = "efi_devops";
    $dbname = "build_stats";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $arr = explode(", ", $cb);
    $cb_str = '';

    $query  = "SELECT region_id, build_date, AVG(build_time) AS mean ";
    $query .= "FROM requests ";
    $query .= "WHERE ( code_base_id = -14";
    foreach($arr as $value){
        $cb_str .= " OR code_base_id = ".$value;
    }
    $query .= $cb_str.") ";
    $query .= "AND build_date >= ".$start." ";
    $query .= "AND build_date < ".$end." ";
    $query .= "AND os_id = ".$os." ";
    $query .= "GROUP BY region_id, build_date";

    //echo var_dump($query);

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // output data of each row
        $data = '[';
        $data_1 = "[";
        $data_2 = "[";
        $data_3 = "[";
        $data_4 = "[";


        $reg_1 = array();
        $reg_2 = array();
        $reg_3 = array();
        $reg_4 = array();

        while($row = $result->fetch_assoc()) {

            //Save the data to region arrays...
            $state = $row['region_id'];
            switch($state){
                case 1: $reg_1[] = $row;  break;
                case 2: $reg_2[] = $row;  break;
                case 3: $reg_3[] = $row;  break;
                case 4: $reg_4[] = $row;  break;
            }  
        }

        
        $size = sizeof($date_arr);

        //Fill in array with missing dates for region 1
        $index = 0;
        for($i = 0; $i < $size; $i++){
            if( $date_arr[$i] != $reg_1[$index]['build_date']) {
                $data_1 .= "null, "; 

            } else {
                $data_1 .= $reg_1[$index]['mean'].", ";
                $index++;
            }
        }

        //Fill in array with missing dates for region 2
        $index = 0;
        for($i = 0; $i < $size; $i++){
            if( $date_arr[$i] != $reg_2[$index]['build_date']) {
                $data_2 .= "null, "; 

            } else {
                $data_2 .= $reg_2[$index]['mean'].", ";
                $index++;
            }
        }

        //Fill in array with missing dates for region 3
        $index = 0;
        for($i = 0; $i < $size; $i++){
            if( $date_arr[$i] != $reg_3[$index]['build_date']) {
                $data_3 .= "null, "; 

            } else {
                $data_3 .= $reg_3[$index]['mean'].", ";
                $index++;
            }
        }

        //Fill in array with missing dates for region 4
        $index = 0;
        for($i = 0; $i < $size; $i++){
            if( $date_arr[$i] != $reg_4[$index]['build_date']) {
                $data_4 .= "null, "; 

            } else {
                $data_4 .= $reg_4[$index]['mean'].", ";
                $index++;
            }
        }

        //Close out string with 'dummy' data... Will be removed later in JS.
        $data_1 .= "null]";
        $data_2 .= "null]";
        $data_3 .= "null]";
        $data_4 .= "null]";

        //Add all the data to the string...
        $data = "[".$data_1.",".$data_2.",".$data_3.",".$data_4."]";
    } else {
        $data = "[null, null]";
    }

    $conn->close();
    return $data;
}

/******************************************************************************
 *
 *****************************************************************************/
function update_weekly_graph($start, $end, $os, $cb, $date_arr, $region){
    $data = '';
    $servername = "codesearch";
    $username = "efi_devops";
    $password = "efi_devops";
    $dbname = "build_stats";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $arr = explode(", ", $cb);
    $cb_str = '';


    $query  = "";
    $query .= "SELECT start_date, code_base_id, total, mean ";
    $query .= "FROM weekly_stats ";
    $query .= "WHERE start_date >= ".$start." ";
    $query .= "AND end_date <= ".$end." ";
    $query .= "AND region_id = ".$region." ";
    $query .= "AND os_id = ".$os." ";
    $query .= "AND ( code_base_id = -14";
    foreach($arr as $value){
        $cb_str .= " OR code_base_id = ".$value;
    }
    $query .= $cb_str.") ";
    $query .= "ORDER BY code_base_id ASC, start_date ASC";

    //echo var_dump($query);

    $result = $conn->query($query);
    $data = "";
    $arr = array();
    if ($result->num_rows > 0) {
        $data = "[";
      
        while($row = $result->fetch_assoc()) {
            $arr[] = $row['start_date'].",".$row['mean'].",".$row['total'];
        }

        $date_size = sizeof($date_arr);
        $arr_size = sizeof($arr);
        $index = 0;
        $mean = 0;
        $total = 0;

        for($i = 0; $i < $date_size; $i++){
            $mean = 0;
            $total = 0;
            $index = 0;

            while($index < $arr_size){
                $foo = explode(',', $arr[$index]);
                
                if($date_arr[$i] == intval($foo[0])){
                    $mean = $mean + ($foo[1] * $foo[2]);
                    $total = $total + $foo[2];
                }
                $index++;
            }//end while

            //Fill data...
            if($mean == 0){
                $data .= " null,";
            } else {
                $data .= ($mean/$total).",";
            }
        }

        $data .= " null]";
    } else {
        $data = "[null, null]";
    }

    $conn->close();
    return $data;
}

/******************************************************************************
 *
 *****************************************************************************/
function get_data($start, $end, $os, $weekly_view){

    $fremont = array();
    $vc_fc   = array();
    $idc     = array();
    $vc_idc  = array();

    $fremont = get_data_query($start, $end, 1, $os);
    $vc_fc   = get_data_query($start, $end, 2, $os);
    $idc     = get_data_query($start, $end, 3, $os);
    $vc_idc  = get_data_query($start, $end, 4, $os);

    $full_count = Merge_Lists_Count($fremont, $vc_fc, $idc, $vc_idc);
    $full_mean  = Merge_Lists_Mean($fremont, $vc_fc, $idc, $vc_idc);
    $table = Build_Table($full_count, $full_mean);

    return $table;
}

/******************************************************************************
 *
 *****************************************************************************/
function get_data_query($start, $end, $region, $os){
    $servername = "codesearch";
    $username = "efi_devops";
    $password = "efi_devops";
    $dbname = "build_stats";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $query  = "SELECT cb.name, COUNT(r.code_base_id) AS total, AVG(build_time) AS mean ";
    $query .= "FROM requests AS r, code_base AS cb ";
    $query .= "WHERE r.build_date >= ".$start." ";
    $query .= "AND r.build_date < ".$end." ";
    $query .= "AND r.code_base_id = cb.id ";
    $query .= "AND os_id = ".$os." ";
    $query .= "AND region_id = ".$region." ";
    $query .= "GROUP BY cb.name";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // output data of each row
        $data = array();
        while($row = $result->fetch_assoc()) {
            $data[] = $row['name'].", ".$row['total'].", ".$row['mean'];
        }
    } else {
        //echo "{\"count\": 0}";
        $data[] = "NO DATA, 0, 0";
    }
    $conn->close();
    return $data;
}

/******************************************************************************
 *
 *****************************************************************************/
function Build_Table($arr_count, $arr_mean){
/*
    $arr_cb = array();
    $arr_cb[] = 'flame10';
    $arr_cb[] = 'flame20'; 
    $arr_cb[] = 'flame30';
    $arr_cb[] = 'flame40';
    $arr_cb[] = 'flame45';
    $arr_cb[] = 'flame50';
    $arr_cb[] = 'flame60';
    $arr_cb[] = 'emerald10';
    $arr_cb[] = 'barracuda10';
    $arr_cb[] = 'ice21';
    $arr_cb[] = 'ice22';
    $arr_cb[] = 'ice23';
    $arr_cb[] = 'tourmaline10';
    $arr_cb[] = 'sky21';
    $arr_cb[] = 'sky22';
    $arr_cb[] = 'sky23';
*/


    $arr_cb = array();
    $arr_cb[] = 'flame60';
    $arr_cb[] = 'flame50';
    $arr_cb[] = 'flame45';    
    $arr_cb[] = 'flame40';
    $arr_cb[] = 'flame30';
    $arr_cb[] = 'flame20';     
    $arr_cb[] = 'flame10';
    $arr_cb[] = 'barracuda10';
    $arr_cb[] = 'ice23';
    $arr_cb[] = 'ice22';
    $arr_cb[] = 'ice21';  
    $arr_cb[] = 'sky23';
    $arr_cb[] = 'sky22';
    $arr_cb[] = 'sky21';    
    $arr_cb[] = 'emerald10';
    $arr_cb[] = 'tourmaline10';


    
    $table = "<table id='stat_table'>";
    $table .= "<tr><td><b>Codebase:</td><td><b>Fremont:</b></td><td><b>vCommander Fremont:</b></td><td><b>IDC:</b></td><td><b>vCommander IDC:</b></td></tr>";

    $size = sizeof($arr_cb);
    for($i = 0; $i < $size; $i++){
        if($arr_count[0][$arr_cb[$i]] != 0 || $arr_count[1][$arr_cb[$i]] != 0 || $arr_count[2][$arr_cb[$i]] != 0 || $arr_count[3][$arr_cb[$i]] != 0 ){
            //Count Data
            $td_count_fc = "Count: <b>".$arr_count[0][$arr_cb[$i]]."</b>";
            $td_count_vc_fc = "Count: <b>".$arr_count[1][$arr_cb[$i]]."</b>";
            $td_count_idc = "Count: <b>".$arr_count[2][$arr_cb[$i]]."</b>";
            $td_count_vc_idc = "Count: <b>".$arr_count[3][$arr_cb[$i]]."</b>";

            //Mean Data
            $td_mean_fc = "Mean: <b>".$arr_mean[0][$arr_cb[$i]]." min</b>";
            $td_mean_vc_fc = "Mean: <b>".$arr_mean[1][$arr_cb[$i]]." min</b>";
            $td_mean_idc = "Mean: <b>".$arr_mean[2][$arr_cb[$i]]." min</b>";
            $td_mean_vc_idc = "Mean: <b>".$arr_mean[3][$arr_cb[$i]]." min</b>";

            //Check for zero count
            if($td_count_fc == "Count: <b>0</b>"){
                $td_count_fc = "<font color='grey'>".$td_count_fc."</font>";
                $td_mean_fc = "&nbsp;";
            }
            if($td_count_vc_fc == "Count: <b>0</b>"){
                $td_count_vc_fc = "<font color='grey'>".$td_count_vc_fc."</font>";
                $td_mean_vc_fc = "&nbsp;";                
            }
            if($td_count_idc == "Count: <b>0</b>"){
                $td_count_idc = "<font color='grey'>".$td_count_idc."</font>";
                $td_mean_idc = "&nbsp;";                
            }
            if($td_count_vc_idc == "Count: <b>0</b>"){
                $td_count_vc_idc = "<font color='grey'>".$td_count_vc_idc."</font>";
                $td_mean_vc_idc = "&nbsp;";
                
            }
//<input type='checkbox' name='cb_code_base' value='".$i+1."'> 
            //Construct table
            $table .= "<tr><td id='ot_td'><input type='checkbox' name='cb_code_base[]' value='".$arr_cb[$i]."' class='cb_checkbox_group' checked onclick='Update_Graph()'>".$arr_cb[$i]."</td>";
            $table .= "<td id='ot_td'>".$td_count_fc."<br>".$td_mean_fc."</td>";
            $table .= "<td id='ot_td'>".$td_count_vc_fc."<br>".$td_mean_vc_fc."</td>";
            $table .= "<td id='ot_td'>".$td_count_idc."<br>".$td_mean_idc."</td>";
            $table .= "<td id='ot_td'>".$td_count_vc_idc."<br>".$td_mean_vc_idc."</td></tr>";
        }    
    }
  
    $table .= "</table>";
    $table = "<center>".$table."</center>";
    return $table;
    //return implode($arr_count[0]);
}


/******************************************************************************
 *
 *****************************************************************************/
function Merge_Lists_Mean($fremont, $vc_fc, $idc, $vc_idc){
    $r1 = array(
        'flame10'      => 'N/A',
        'flame20'      => 'N/A',
        'flame30'      => 'N/A',
        'flame40'      => 'N/A',
        'flame45'      => 'N/A',
        'flame50'      => 'N/A',
        'flame60'      => 'N/A',
        'emerald10'    => 'N/A',
        'barracuda10'  => 'N/A',
        'ice21'        => 'N/A',
        'ice22'        => 'N/A',
        'ice23'        => 'N/A',
        'tourmaline10' => 'N/A',
        'sky21'        => 'N/A',
        'sky22'        => 'N/A',
        'sky23'        => 'N/A'
    );

    $size = sizeof($fremont);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $fremont[$i]);
         $r1[$arr[0]] = intval($arr[2]);
    }

    $r2 = array(
        'flame10'      => 'N/A',
        'flame20'      => 'N/A',
        'flame30'      => 'N/A',
        'flame40'      => 'N/A',
        'flame45'      => 'N/A',
        'flame50'      => 'N/A',
        'flame60'      => 'N/A',
        'emerald10'    => 'N/A',
        'barracuda10'  => 'N/A',
        'ice21'        => 'N/A',
        'ice22'        => 'N/A',
        'ice23'        => 'N/A',
        'tourmaline10' => 'N/A',
        'sky21'        => 'N/A',
        'sky22'        => 'N/A',
        'sky23'        => 'N/A'
    );

    $size = sizeof($vc_fc);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $vc_fc[$i]);
         $r2[$arr[0]] = intval($arr[2]);
    }

    $r3 = array(
        'flame10'      => 'N/A',
        'flame20'      => 'N/A',
        'flame30'      => 'N/A',
        'flame40'      => 'N/A',
        'flame45'      => 'N/A',
        'flame50'      => 'N/A',
        'flame60'      => 'N/A',
        'emerald10'    => 'N/A',
        'barracuda10'  => 'N/A',
        'ice21'        => 'N/A',
        'ice22'        => 'N/A',
        'ice23'        => 'N/A',
        'tourmaline10' => 'N/A',
        'sky21'        => 'N/A',
        'sky22'        => 'N/A',
        'sky23'        => 'N/A'
    );

    $size = sizeof($idc);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $idc[$i]);
         $r3[$arr[0]] = intval($arr[2]);
    }

    $r4 = array(
        'flame10'      => 'N/A',
        'flame20'      => 'N/A',
        'flame30'      => 'N/A',
        'flame40'      => 'N/A',
        'flame45'      => 'N/A',
        'flame50'      => 'N/A',
        'flame60'      => 'N/A',
        'emerald10'    => 'N/A',
        'barracuda10'  => 'N/A',
        'ice21'        => 'N/A',
        'ice22'        => 'N/A',
        'ice23'        => 'N/A',
        'tourmaline10' => 'N/A',
        'sky21'        => 'N/A',
        'sky22'        => 'N/A',
        'sky23'        => 'N/A'
    );

    $size = sizeof($vc_idc);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $vc_idc[$i]);
         $r4[$arr[0]] = intval($arr[2]);
    }

    $list = array();

    $list[] = $r1;
    $list[] = $r2;
    $list[] = $r3;
    $list[] = $r4;
    
    //echo var_dump($list);
    return $list;
}

/******************************************************************************
 *
 *****************************************************************************/
function Merge_Lists_Count($fremont, $vc_fc, $idc, $vc_idc){
    $r1 = array(
        'flame10'      => 0,
        'flame20'      => 0,
        'flame30'      => 0,
        'flame40'      => 0,
        'flame45'      => 0,
        'flame50'      => 0,
        'flame60'      => 0,
        'emerald10'    => 0,
        'barracuda10'  => 0,
        'ice21'        => 0,
        'ice22'        => 0,
        'ice23'        => 0,
        'tourmaline10' => 0,
        'sky21'        => 0,
        'sky22'        => 0,
        'sky23'        => 0
    );

    $size = sizeof($fremont);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $fremont[$i]);
         $r1[$arr[0]] = intval($arr[1]);
    }

    $r2 = array(
        'flame10'      => 0,
        'flame20'      => 0,
        'flame30'      => 0,
        'flame40'      => 0,
        'flame45'      => 0,
        'flame50'      => 0,
        'flame60'      => 0,
        'emerald10'    => 0,
        'barracuda10'  => 0,
        'ice21'        => 0,
        'ice22'        => 0,
        'ice23'        => 0,
        'tourmaline10' => 0,
        'sky21'        => 0,
        'sky22'        => 0,
        'sky23'        => 0
    );

    $size = sizeof($vc_fc);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $vc_fc[$i]);
         $r2[$arr[0]] = intval($arr[1]);
    }

    $r3 = array(
        'flame10'      => 0,
        'flame20'      => 0,
        'flame30'      => 0,
        'flame40'      => 0,
        'flame45'      => 0,
        'flame50'      => 0,
        'flame60'      => 0,
        'emerald10'    => 0,
        'barracuda10'  => 0,
        'ice21'        => 0,
        'ice22'        => 0,
        'ice23'        => 0,
        'tourmaline10' => 0,
        'sky21'        => 0,
        'sky22'        => 0,
        'sky23'        => 0
    );

    $size = sizeof($idc);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $idc[$i]);
         $r3[$arr[0]] = intval($arr[1]);
    }

    $r4 = array(
        'flame10'      => 0,
        'flame20'      => 0,
        'flame30'      => 0,
        'flame40'      => 0,
        'flame45'      => 0,
        'flame50'      => 0,
        'flame60'      => 0,
        'emerald10'    => 0,
        'barracuda10'  => 0,
        'ice21'        => 0,
        'ice22'        => 0,
        'ice23'        => 0,
        'tourmaline10' => 0,
        'sky21'        => 0,
        'sky22'        => 0,
        'sky23'        => 0
    );

    $size = sizeof($vc_idc);
    for($i = 0; $i < $size; $i++ ){
         $arr = explode(", ", $vc_idc[$i]);
         $r4[$arr[0]] = intval($arr[1]);
    }

    $list = array();

    $list[] = $r1;
    $list[] = $r2;
    $list[] = $r3;
    $list[] = $r4;
    
    //echo var_dump($list);
    return $list;
}
/*
+----+--------------+
| id | name         |
+----+--------------+
|  1 | flame10      |
|  2 | flame20      |
|  3 | flame30      |
|  4 | flame40      |
|  5 | flame45      |
|  6 | flame50      |
|  7 | flame60      |
|  8 | emerald10    |
|  9 | barracuda10  |
| 10 | ice21        |
| 11 | ice22        |
| 12 | ice23        |
| 13 | tourmaline10 |
| 14 | sky21        |
| 15 | sky22        |
| 16 | sky23        |
+----+--------------+

*/

/*
+----+------------+
| id | name       |
+----+------------+
|  1 | Fremont    |
|  2 | VC_Fremont |
|  3 | IDC        |
|  4 | VC_IDC     |
+----+------------+
*/    


//$ mysql --host=codesearch build_stats --user=efi_devops --password=efi_devops
//select avg(build_time) from requests where code_base_id = 6 and build_date >= 20150830 and build_date < 20151207 and region_id = 1 and os_id = 1

/*
mysql> select cb.name, count(r.code_base_id) as totals from requests as r, code_base as cb where r.build_date >= 20160824 and r.build_date < 20160825 and r.code_base_id = cb.id and os_id = 1 and region_id = 1 group by cb.name;
+---------+--------+
| name    | totals |
+---------+--------+
| flame30 |      3 |
| flame45 |      2 |
| flame50 |     15 |
| flame60 |     17 |
+---------+--------+
4 rows in set (0.00 sec)
*/

?>
