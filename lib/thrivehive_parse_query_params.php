<?php

// Receive an array of keys and type data, and parse the corresponding query parameters from the request.
// Returns an array with `data` and `error` properties

// Example of $param_types value:
//   array(
//       "name" => array("type" => "string"),
//       "registered" => array("type" => "boolean", "required": false)
//   )
// Note: if "required" is not specified, it defaults to true

function parse_params($param_types) {
    $data = array();
    $errors = array();

    foreach($param_types as $param => $param_options) {
        // "required" is optional, and defaults to true if omitted
        $required = !isset($param_options["required"]) || $param_options["required"];
        $param_type = $param_options["type"];

        if ($required && !isset($_REQUEST[$param])) {
            array_push($errors, "The `$param` parameter is required.");
            continue;
        }

        $val = $_REQUEST[$param];
        $parsed_val = parse_query_string($val, $param_type);

        // NULL is used to indicate parse failure, but can also be a valid value for optional parameters
        // So we have a failure if the parsed value is null but the original wasn't null.
        if ($parsed_val === NULL && $val !== NULL) {
            array_push($errors, "The `$param` parameter must be convertible to type `$param_type`");
            continue;
        }

        $data[$param] = stripslashes($parsed_val);
    }

    return compact("data", "errors");
}

// Convert a query string to the desired type. Return value NULL signifies an error.
// Currently returns strings, booleans, or integers.
function parse_query_string($value, $type) {
    switch ($type) {
    case "string":
        return $value;
    case "boolean":
        switch (strtolower($value)) {
        case "true":
            return TRUE;
        case "false":
            return FALSE;
        default:
            return NULL;
        }
    case "integer":
        return intval($value);
    }
}

?>

