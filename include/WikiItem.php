<?php


class WikiItem extends WfoFacets{


    // for working the singletons
    protected static $loaded = array();
    private int $id;
    private string $label;
    private object $data;


    private function __construct($q_number, $label, $data_json){
        $this->id = $q_number;
        $this->label = $label;
        $this->data = json_decode($data_json);
        self::$loaded[$q_number] = $this;
    }

    public static function getWikiItem($q_number, $force_fetch = false){

        global $mysqli;

        // we are either passed an integer or a q value 
        // we like it as an int for the database
        if(preg_match('/^Q[0-9]+$/', $q_number)){
            $init_value = (int)substr($q_number, 1);
        }elseif(preg_match('/^[0-9]+$/', $q_number)){
            $init_value = (int)$q_number;
        }else{
             throw new ErrorException("Unrecognized Q number format $q_number"); 
        }

        // we are singleton so load it on that basis
        if(isset(self::$loaded[$init_value]) && $force_fetch == false){
            return self::$loaded[$init_value];
        }else{

            // we are not returning just what is in memory so 
            // we should look to get it from the db if we can
            $response = $mysqli->query("SELECT * FROM wiki_cache WHERE q_number = $init_value");
            if($response->num_rows){
                $row = $response->fetch_assoc();
            }else{
                $row = false;
            }

            // we have it and they don't want to refresh it so just return it
            if(!$force_fetch && $row){
                return new WikiItem($init_value, $row['label_en'], $row['data_json']);
            }

            // we got to here so we didn't find it in the db or they want to refresh the cache
            // let's get it from wikidata
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "Accept: application/json"
                ]
            ];

            // DOCS: https://www.php.net/manual/en/function.stream-context-create.php
            $context = stream_context_create($opts);

            // Open the file using the HTTP headers set above
            // DOCS: https://www.php.net/manual/en/function.file-get-contents.php
            $q = "Q$init_value";
            $json = file_get_contents("https://www.wikidata.org/entity/$q" , false, $context);
            if(!$json){
                throw new ErrorException("Could not retrieve data for Q$q_number");
            }
            $data = json_decode($json);
            if(isset($data->entities->{$q}->labels->en->value)){
                $label = $data->entities->{$q}->labels->en->value;
            }else{
                $label = $q;
            }

            if(strlen($label) > 100){
                $label = substr($label, 0, 95) . ' ...';
            }

            // update the db
            $json_safe = $mysqli->real_escape_string($json);
            $label_safe = $mysqli->real_escape_string($label);
            if($row){
                $mysqli->query("UPDATE `wiki_cache` SET `label_en` = '$label_safe', `data_json` = '$json_safe' WHERE `q_number` = $init_value");
            }else{
                $mysqli->query("INSERT INTO `wiki_cache` (`q_number`, `label_en`, `data_json`) VALUES ($init_value, '$label_safe','$json_safe')");
            }

            if($mysqli->error){
                throw new ErrorException($mysqli->error);
            }

            return new WikiItem($init_value, $label, $json);

        }
        

    }

    public function getId(){
        return $this->id;
    }

    public function getLabel(){
        return $this->label;
    }


}// class