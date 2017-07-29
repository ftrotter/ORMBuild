<?php


$json_text = '{ "schema": {
      "title":"User Feedback",
      "description":"What do you think about Alpaca?",
      "type":"object",
      "properties": {
        "name": {
          "type":"string",
          "title":"Name"
        },
        "ranking": {
          "type":"string",
          "title":"Ranking",
          "enum":["excellent","not too shabby","alpaca built my hotrod"]
        }
      }
    }
  }
  ';

    echo "\n$json_text\n\n";

    $php_array = json_decode($json_text, true);

    var_export($php_array);

    $new_json = json_encode($php_array, JSON_PRETTY_PRINT);

    echo "\n\n$new_json\n\n";
