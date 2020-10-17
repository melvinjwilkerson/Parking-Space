<?php
    // Adding Required Script for generating Elapsed Time
    require external_scripts . "Elapsed.php";

    $driver_and_parking_details = getParkingDetails(htmlentities($_POST['parking-id']));
    
    // Function for getting the parking spot details from the database
    function getParkingDetails($parking_id){
        // Checking whether the parking spot exists
        $does_parking_exist = checkWhetherParkingSpotExists($parking_id);

        // What to do depending on whether it exists
        if (!$does_parking_exist)
        {
            return FALSE;
        }
        else if ($does_parking_exist)
        {
            // Using Prepared Statements to avoid SQL injections
            // Checking for connection to DB
            $connection_status = checkConnection();

            if ($connection_status !== TRUE)
            {
                // If there's a connection error
                header("Location: Checkout.php?checkout_error=$connection_status") or die();
            } 
            else 
            {
                // Connecting to the database
                $conn = connectToDatabase();

                //The query for checking for the parking details in the database
                $query =    "SELECT DRIVER_ID, USERNAME, BOOKINGS.P_ID, TIME_IN, P_LOCATION
                            FROM BOOKINGS, PARKING
                            WHERE BOOKINGS.P_ID = PARKING.P_ID 
                            AND BOOKINGS.P_ID = ?
                            AND TIME_OUT IS NULL";
            
                // Preparing and executing the query
                if (!$stmt =  $conn->stmt_init())
                {
                    // Error initializing the SQL Statement
                    header("Location: Checkout.php?checkout_error=sql_init") or die();
                    return FALSE;
                }

                else if (!$stmt = $conn->prepare($query))
                {
                    // Error preparing the SQL Statement
                    header("Location: Checkout.php?checkout_error=sql_prepare") or die();
                    return FALSE;
                }
                
                else if (!$stmt->bind_param("i", $parking_id))
                {
                    // Error binding parameters
                    header("Location: Checkout.php?checkout_error=sql_bind") or die();
                    return FALSE;
                }
                
                else if (!$stmt->execute())
                {
                    // Execute query
                    header("Location: Checkout.php?checkout_error=sql_execute") or die();
                    return FALSE;
                }
                
                else
                {
                    // Getting the results of the query
                    $fetched = $stmt->get_result();
                    $result = mysqli_fetch_all($fetched, MYSQLI_ASSOC);

                    if (empty($result))
                    {    
                        // The username or e-mail isn't in the database
                        header("Location: Checkout.php?checkout_error=parking_empty") or die();
                        return FALSE;
                    }
                    else
                    {
                        return $result;
                    }
                }
            }
        }
    }

    function checkWhetherParkingSpotExists($parking_id){
        // Using Prepared Statements to avoid SQL injections
        // Checking for connection to DB
        $connection_status = checkConnection();

        if ($connection_status !== TRUE)
        {
            // If there's a connection error
            header("Location: Checkout.php?checkout_error=$connection_status") or die();
        } 
        else 
        {
            // Connecting to the database
            $conn = connectToDatabase();

            //The query for checking for the parking details in the database
            $query  =   "SELECT P_ID FROM PARKING WHERE P_ID = ?";
        
            // Preparing and executing the query
            if (!$stmt =  $conn->stmt_init())
            {
                // Error initializing the SQL Statement
                header("Location: Checkout.php?checkout_error=sql_init") or die();
                return FALSE;
            }

            else if (!$stmt = $conn->prepare($query))
            {
                // Error preparing the SQL Statement
                header("Location: Checkout.php?checkout_error=sql_prepare") or die();
                return FALSE;
            }
            
            else if (!$stmt->bind_param("i", $parking_id))
            {
                // Error binding parameters
                header("Location: Checkout.php?checkout_error=sql_bind") or die();
                return FALSE;
            }
            
            else if (!$stmt->execute())
            {
                // Execute query
                header("Location: Checkout.php?checkout_error=sql_execute") or die();
                return FALSE;
            }
            
            else
            {
                // Getting the results of the query
                $fetched = $stmt->get_result();
                $result = mysqli_fetch_all($fetched, MYSQLI_ASSOC);

                if (empty($result))
                {    
                    // The username or e-mail isn't in the database
                    header("Location: Checkout.php?checkout_error=parkin_dne") or die();
                    return FALSE;
                }
                else
                {
                    return TRUE;
                }
            }
        }
    }

    function getCharges($time_in){
        if (settings['charges']['enabled']){
            // Calculating the time difference
            $now = strtotime("now");
            $time_in = strtotime($time_in);
            $difference = $now - $time_in;

            // Getting set charging duration and cost from settings
            $charging_duration = settings['charges']['duration'];
            $charging_cost = settings['charges']['cost'];

            // Calculating difference in hours
            $cost_multiplier = (floor($difference/(60 * $charging_duration)));
            
            // Getting the total cost the driver has to pay
            $total_cost = $cost_multiplier * $charging_cost;

            return $total_cost;
        } else {
            return FALSE;
        }
    }

    /*
        After getting the details, we display them to the user for them to confirm the spot
        We also show the amount of time they've spent on the spot
    */

    function outputParkingDetails(){
        if ($GLOBALS['driver_and_parking_details'] === FALSE)
        {
            // The parking spot doesn't exist
            return array(
                "Question" => "Parking details not found.",
                "Details" => "Parking Spot #$parking_id doesn't exist.",
                "Time" => "Please input a correct Parking Ticket Number",
                "Status" => FALSE,
                "Buttons" => confirmParkingButtons(FALSE),
            );
        } 
       
        else if (empty($GLOBALS['driver_and_parking_details']))
        {
            return array(
                "Question" => "The spot is not occupied.",
                "Details" => "Parking Spot #$parking_id isn't occupied.",
                "Time" => "Please input a correct Parking Ticket Number",
                "Status" => FALSE,
                "Buttons" => confirmParkingButtons(FALSE),
            );
        } 
        
        else 
        {
            // Getting the Parking Spot's details
            $driver_id = $GLOBALS['driver_and_parking_details'][0]["DRIVER_ID"];
            $username = $GLOBALS['driver_and_parking_details'][0]["USERNAME"];
            $parking_id = $GLOBALS['driver_and_parking_details'][0]["P_ID"];
            $parking_location = $GLOBALS['driver_and_parking_details'][0]["P_LOCATION"];
            $charges = getCharges($GLOBALS['driver_and_parking_details'][0]["TIME_IN"]);
            
            // Calculating and Formatting Time for Output
            // EXTERNAL -> time_elapsed_string() -> Function for displaying elapsed time.
            $elapsed_time = time_elapsed_string($GLOBALS['driver_and_parking_details'][0]["TIME_IN"]);

            // Returning the driver's details for confirmation
            $outputs = array(
                "Question" => "Is this your parking spot?",
                "Details" => "Driver #$driver_id ($username) with Parking Spot #$parking_id at $parking_location",
                "Time" => "You parked here $elapsed_time.",
                "Status" => TRUE,
                "Buttons" => confirmParkingButtons(TRUE),
            );

            if ($charges !== FALSE){
                $outputs["Charges"] = "Your parking fee is Ksh.$charges";
            }
        }
        return $outputs;
        
    }

    // Saving the details once the driver has confirmed
    function saveDetailsToSession(){
        $_SESSION['driver_and_parking_details'] = $GLOBALS['driver_and_parking_details'];
    }

    function confirmParkingButtons($status){
        if ($status === TRUE){
            return  "<div class='confirm'>".
                        "<input type='submit' value='Confirm'>".
                    "</div>".
                    "<div>".
                        "<a href='" . "javascript:history.back()" . "'><button type='button'>No</button></a>".
                    "</div>";
        } else {
            header("refresh:15; url=Checkout.php");
            return  "<div>".
                        "<a href='" . "javascript:history.back()" . "'><button type='button'>Return</button></a>".
                    "</div>";
                    
        }
    }
?>