<?php
require_once('db.php');

$sessionId   = $_POST["sessionId"] ?? '';
$serviceCode = $_POST["serviceCode"] ?? '';
$phoneNumber = $_POST["phoneNumber"] ?? '';
$text        = $_POST["text"] ?? '';


$textArray = explode("*", $text);
$level = count($textArray);

if ($text == "") {
    echo "CON Welcome to the Marks Appeal System\n";
    echo "1. Check my marks\n";
    echo "2. Appeal my marks\n";
    echo "3. Exit";
} elseif ($text == "1") {
    echo "CON Enter your Student ID:";
} elseif ($textArray[0] == "1" && $level == 2) {
    $student_id = $textArray[1];
    $stmt = $conn->prepare("SELECT m.module_name, sm.mark FROM student_marks sm JOIN modules m ON sm.module_id = m.module_id WHERE sm.student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response = "END Your Marks:\n";
        while ($row = $result->fetch_assoc()) {
            $response .= $row['module_name'] . ": " . $row['mark'] . "\n";
        }
        echo $response;
    } else {
        echo "END Error: Student ID not found. Please try again or contact support.";
    }
} elseif ($text == "2") {
    echo "CON Enter your Student ID:";
} elseif ($textArray[0] == "2" && $level == 2) {
    $student_id = $textArray[1];

    $stmt = $conn->prepare("SELECT sm.module_id, m.module_name, sm.mark FROM student_marks sm JOIN modules m ON sm.module_id = m.module_id WHERE sm.student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response = "CON Select the module to appeal:\n";
        $i = 1;
        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $response .= "$i. " . $row['module_name'] . ": " . $row['mark'] . "\n";
            $modules[$i] = [
                "module_id" => $row['module_id'],
                "name" => $row['module_name'],
                "mark" => $row['mark']
            ];
            $i++;
        }
        $response .= "0. Go Back";
        file_put_contents("session_$sessionId.json", json_encode(["student_id" => $student_id, "modules" => $modules]));
        echo $response;
    } else {
        echo "END Error: Student ID not found. Please try again.";
    }
} elseif ($textArray[0] == "2" && $level == 3) {
    $moduleIndex = $textArray[2];
    $session = json_decode(file_get_contents("session_$sessionId.json"), true);

    if ($moduleIndex == "0") {
        echo "END Cancelled.";
    } elseif (isset($session['modules'][$moduleIndex])) {
        echo "CON Please provide a brief reason for your appeal:";
        $session['selected_module'] = $moduleIndex;
        file_put_contents("session_$sessionId.json", json_encode($session));
    } else {
        echo "END Invalid module selection.";
    }
} elseif ($textArray[0] == "2" && $level == 4) {
    $reason = $textArray[3];
    $session = json_decode(file_get_contents("session_$sessionId.json"), true);
    $student_id = $session['student_id'];
    $module_id = $session['modules'][$session['selected_module']]['module_id'];

    $stmt = $conn->prepare("INSERT INTO appeals (student_id, module_id, reason) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $student_id, $module_id, $reason);
    $stmt->execute();

    echo "END Thank you. Your appeal has been submitted successfully. You will be notified of the outcome soon.";
    unlink("session_$sessionId.json"); // cleanup
} elseif ($text == "3") {
    echo "END Thank you for using the system.";
} else {
    echo "END Invalid input.";
}
