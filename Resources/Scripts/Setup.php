<!--First Time Setup-->
<!-- A slimmed version of the hilariously verbose file 😂-->
<?php
    // Files to Include
    require $_SERVER['DOCUMENT_ROOT'] . "/Resources/Scripts/SQL.php";

    // Parsing tables from Tables.ini and using them as a constant
    define('tables', parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/Resources/Settings/Tables.ini", TRUE));

    // Function for creating the database
    function createDatabase($database = settings['server']['db']){
        if (checkConnection() === "db_error"){
            // checkConnection returning db_error means that the database doesn't exist
            return runQuery("CREATE DATABASE " . $database);
        } else {
            return "db_exists";
        }
    }

    // Function for creating the tables
    function createTables($tables = tables){
        foreach ($tables as $table => $options){
            $result[$table] = connectToDatabase() -> query($options["SCHEMA"]);
        }
        return $result;
    }

    // Query for adding the test data
    // function addTestData($data_array){
    //     foreach($data_array as $data){
    //         $result = runQuery($data);
    //     }
    //     return $result;
    // }

    function setup($tables = tables, $database = settings['server']['db']){
        // NOTE: The variables are used for error detection
        // First we create the Database
        $database_result = createDatabase($database);
        if ( $database_result === TRUE || $database_result === "db_exists"){
            runQuery("USE $database");
        }

        // Then we create the Tables
        $table_result = createTables($tables);

        // // Then we add the test data for each table
        // foreach($tables as $table => $options){
        //     $data_result[$table] = addTestData($options["DATA"]);
        // }

        return array(
            $database_result,
            $table_result
        );
    }

    echo "<pre>";
    print_r(setup());
    echo "</pre>";
?>