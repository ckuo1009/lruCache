<?php

// node class to implement doubly linkedList
class Node {
    public $key;
    public $value;
    public $prev = null;
    public $next = null;

    public function __construct($key, $value) {
        $this->key = $key;
        $this->value = $value;
    }
}

class LruCache {
    private $capacity;
    // array to save the key
    private $map = [];

    private $head = null;
    private $tail = null;
    // count the current size of the cache
    private $currentSize = 0;

    public function __construct($size) {
        if (!is_int($size) || $size <= 0) {// if the size is <=0 , throw new exception
            throw new InvalidArgumentException("Size must be a positive integer.<br>");
        }
        $this->capacity = $size;
    }
    // put the the node in front of the list. 
    // when a new node was added or
    // when a node was called get()
    private function addNodeAtFront($node) {
        
        $node->next = $this->head;
        $node->prev = null;

        // the original cache has the head
       if ($this->head !== null) {
            $this->head->prev = $node;
        }
        $this->head = $node;
        // cache is empty
        if ($this->tail === null) {
            $this->tail = $node;
        }
        $this->map[$node->key] = $node;
        $this->currentSize++;
    }
    // remove the node when the node needs to be move to the beginning
    private function removeNode($node) {
        // If the node to be removed has a previous node,
        if ($node->prev !== null) {
            $node->prev->next = $node->next;
        } else {
            // If there is no previous node, it means the node to be removed is the head.
            $this->head = $node->next;
        }
        // If the node to be removed has a next node,
        if ($node->next !== null) {
            $node->next->prev = $node->prev;
        } else {
            // If there is no next node, it means the node to be removed is the tail.
            $this->tail = $node->prev;
        }
        // Remove the node from the map using its key.
        unset($this->map[$node->key]);
        // Decrease the size of the current cache by 1.
        $this->currentSize--;
    }
    //function for moving the node to the front when the get funcion is called
    private function moveToFront($node) {
        $this->removeNode($node);
        
        $this->addNodeAtFront($node);
    }
        // Checks if the cache has reached its capacity.
        private function is_full() {
        return $this->currentSize >= $this->capacity;
    }
   // Retrieves the value associated with a key if it exists in the cache 
   // otherwise returns -1. It also moves the accessed node to the follow the LruCahe rule
    public function get($key) {

        if (!isset($this->map[$key])) {// key does not contain in the cache
            return -1;
        }
        $node = $this->map[$key];
        // move this node to the front base on the rule of Lru Cache
        $this->moveToFront($node);
        return $node->value;
    }
    //reset the cache
   private function reset() {
        
        $this->head = null;
        $this->tail = null;
        $this->map = [];
        $this->currentSize = 0;
    }
    // put a value with a key in the cache
    public function put($key, $value, $reset = false) {
        if ($reset) {
            $this->reset();
        }
        //value is not a postive integer
        if (!is_int($value) || $value <= 0) {
            $this->printCache();
            echo "Value not accept " . (is_int($value) ? "negative" : gettype($value)) . ".<br>";
            return;
        }
        //if key is in the cache, update its value
        if (isset($this->map[$key])) {
            $node = $this->map[$key];
            $node->value = $value;
            $this->moveToFront($node);
        } else {
            // check cache is full, remove the tail node
            if ($this->is_full()) {
                $this->removeNode($this->tail);
            }
            $newNode = new Node($key, $value);
            $this->addNodeAtFront($newNode);
        }
    }
    // display the current cache
    public function printCache() {
        $node = $this->head;
        echo"current cache : [";
        while ($node !== null) {
            echo "  $node->key  :  $node->value  ";
            if ($node->next !== null) echo " , ";
            $node = $node->next;
        }

        echo "]<br>";
    }
}
// test class for LruCache
class LruCacheTest {
    private $lruCache;

    public function __construct(LruCache $lruCache) {
        $this->lruCache = $lruCache;
    }

    
    public function runTests($tests) {
        // Iterate through each test case.
        foreach ($tests as $test) {
            echo "<br>Running test: {$test['name']}<br>";
    
            // Display the cache state before executing the action.
            echo "Before:  ";
            $this->lruCache->printCache();
    
            // Execute the specified action (put/get) and capture the actual output for 'get' actions.
            $actualOutput = $this->executeAction($test);
    
            // Display the cache state after executing the action.
            echo "After:  ";
            $this->lruCache->printCache();
    
            // For 'get' actions, display the actual output.
            if ($test['action'] === 'get') {
                echo "Actual Output: $actualOutput<br>";
            }
    
            // Determine the test result. For 'get' actions, compare the actual output with the expected output.
            // For 'put' actions, the operation is considered successful if it completes.
            if (($test['action'] === 'get' && $actualOutput === $test['expectedOutput']) || $test['action'] === 'put') {
                echo "Test Result: Pass<br>";
            } else {
                echo "Test Result: Fail<br>";
            }
        }
    }
    
    private function executeAction($test) {
        // Execute the action based on the test case definition.
        switch ($test['action']) {
            case 'put':
                // Call the put method of the LRU cache with the provided parameters.
                $this->lruCache->put($test['key'], $test['value'], $test['reset'] ?? false);
                return "Operation Complete"; // Indicate that the put operation completed successfully.
            case 'get':
                // Call the get method of the LRU cache and return the value obtained for the given key.
                return $this->lruCache->get($test['key']);
            default:
                // If an invalid action is specified.
                return "Invalid Test Action";
        }
    }
}    
// the class to process the commands from a text files and execute
class Command {
    public static function readFile($filePath) {
        $commands = [];
        $file = fopen($filePath, "r");
        if ($file) {
            while (($line = fgets($file)) !== false) {
                $commands[] = trim($line);
            }
            fclose($file);
        } else {
            // file 
            echo "Error: Unable to open file.\n";
            return null;
        }
        return $commands;
    }
    //// Parses and executes commands from the input file
    public static function parseAndExecuteCommands($commands, $cache) {
        foreach ($commands as $command) {
            $parts = explode(' ', $command);
            if (count($parts) == 1) {
                // get function
                $key = $parts[0]; 
                echo "get(" . $key . ") returns: " . $cache->get($key) . "<br>";
            } elseif (count($parts) == 3) {
                // put function
                $key = $parts[0];
                $value = intval($parts[1]);
                $reset = strtolower($parts[2]) === 'true';
    
                if (!is_numeric($value) || $value <= 0) {
                    // value is not positive integer
                    echo "Input key is $key, input value is $value, values not accept negative or non-integer.<br>";
                } else {
                    
                    $cache->put($key, $value, $reset);
                }
                
                echo "After put($key, $value, " . var_export($reset, true) . "):<br>";
                $cache->printCache();//print current cache
            } else {
                // wrong command format error
                echo "Error: Invalid command format -> " . $command . "<br>";
            }
        
        }
        echo "<br>------------------------test function-----------------------------<br>";

// test cases
$tests = [
    [
       'name' => 'Put key 1 with value 1',
        'action' => 'put',
        'key' => 1,
        'value'  => 1,
        'reset' => false,
    ],
    
    [
        'name' => 'Put key 6 with value 6',
         'action' => 'put',
         'key' => 6,
         'value'  => 6,
         'reset' => false,
     ],
  
    [
        'name' => '(value is negative )Put key 2 with value -1.5',
        'action' => 'put',
        'key' => 2,
        'value'  => -1.5,
        'reset' => false,
    ],
    [
        'name' => '(String as the key )Put key "sjsu" with value 9',
        'action' => 'put',
        'key' => "sjsu",
        'value'  => 9,
        'reset' => false,
    ],
    [
        'name' => 'Get key 1',
        'action' => 'get',
        'key' => 1,
        'expectedOutput' => 1
    ],
    [
        'name' => 'Put key 7 with value 7',
        'action' => 'put',
        'key' => 7,
        'value'  => 7,
        'reset' => false,
    ],
    [
        'name' => 'Put key 3 with value 3 and reset',
        'action' => 'put',
        'key' => 3,
        'value'  => 3,
        'reset' => true,
    ],
    [
        'name' => 'Put key 4 with value 4 ',
        'action' => 'put',
        'key' => 4,
        'value'  => 4,
        'reset' => false,
    ],
    [
        'name' => 'Get a non-existing key (7)',
        'action' => 'get',
        'key' => 7,
        'expectedOutput' => -1
    ],
    [
        'name' => 'Put existing key 4 with new value 8 ',
        'action' => 'put',
        'key' => 4,
        'value'  => 8,
        'reset' => false,
    ],
    [
        'name' => 'Invalid command format. key =  dog cat dog value  = abc ',
        'action' => 'put',
        'key' => "dog cat dog ",
        'value'  => "abc ",
        'reset' => false,
    ],


];
// initial testCache size 
$testSize=3;


try {
    // create a new LruCache object for test
    $testCache = new LruCache($testSize);
    echo "test LruCache size =  $testSize<br>";

    $tester = new LruCacheTest($testCache);
    $tester->runTests($tests); 
} catch (InvalidArgumentException $e) {
    echo "Exception caught:  " . $e->getMessage();
}
   
}
    

    public static function main() {
        echo <<<_END
        <html><head><title>LRU Cache Command Processor</title></head><body>
        <form method='post' action='mid1.php' enctype='multipart/form-data'>
        Select File: <input type='file' name='filename' size='10'>
        <input type='submit' value='Upload'>
        </form>
        _END;

        if ($_FILES && isset($_FILES['filename'])) {
            $name = $_FILES['filename']['tmp_name'];
            $commands = self::readFile($name);
            if ($commands !== null) {
                
                try{
                    //initial the cache size here 
                    $size =2;
                    $cache = new LruCache(2);
                    echo "<br>Initial Cache with size: $size<br>";
                }catch(InvalidArgumentException $e){
                    echo $e->getMessage();
                }
                
                self::parseAndExecuteCommands($commands, $cache);
            } else {
                echo "Error: File content is not valid or empty.\n";
            }
        }
       
        echo "</body></html>";
    }
}

Command::main();

// Initial Cache with size: 2
// After put(1, 1, false):
// current cache : [ 1 : 1 ]
// Input key is 2, input value is -2, values not accept negative or non-integer.
// After put(2, -2, true):
// current cache : [ 1 : 1 ]
// get(Cs174) returns: -1
// After put(3, 3, false):
// current cache : [ 3 : 3 , 1 : 1 ]
// get(1) returns: 1
// After put(4, 4, true):
// current cache : [ 4 : 4 ]
// get(4) returns: 4
// Error: Invalid command format -> cat dog cat dog cat dog

// ------------------------test function-----------------------------
// test LruCache size = 3

// Running test: Put key 1 with value 1
// Before: current cache : []
// After: current cache : [ 1 : 1 ]
// Test Result: Pass

// Running test: Put key 6 with value 6
// Before: current cache : [ 1 : 1 ]
// After: current cache : [ 6 : 6 , 1 : 1 ]
// Test Result: Pass

// Running test: (value is negative )Put key 2 with value -1.5
// Before: current cache : [ 6 : 6 , 1 : 1 ]
// current cache : [ 6 : 6 , 1 : 1 ]
// Value not accept double.
// After: current cache : [ 6 : 6 , 1 : 1 ]
// Test Result: Pass

// Running test: (String as the key )Put key "sjsu" with value 9
// Before: current cache : [ 6 : 6 , 1 : 1 ]
// After: current cache : [ sjsu : 9 , 6 : 6 , 1 : 1 ]
// Test Result: Pass

// Running test: Get key 1
// Before: current cache : [ sjsu : 9 , 6 : 6 , 1 : 1 ]
// After: current cache : [ 1 : 1 , sjsu : 9 , 6 : 6 ]
// Actual Output: 1
// Test Result: Pass

// Running test: Put key 7 with value 7
// Before: current cache : [ 1 : 1 , sjsu : 9 , 6 : 6 ]
// After: current cache : [ 7 : 7 , 1 : 1 , sjsu : 9 ]
// Test Result: Pass

// Running test: Put key 3 with value 3 and reset
// Before: current cache : [ 7 : 7 , 1 : 1 , sjsu : 9 ]
// After: current cache : [ 3 : 3 ]
// Test Result: Pass

// Running test: Put key 4 with value 4
// Before: current cache : [ 3 : 3 ]
// After: current cache : [ 4 : 4 , 3 : 3 ]
// Test Result: Pass

// Running test: Get a non-existing key (7)
// Before: current cache : [ 4 : 4 , 3 : 3 ]
// After: current cache : [ 4 : 4 , 3 : 3 ]
// Actual Output: -1
// Test Result: Pass

// Running test: Put existing key 4 with new value 8
// Before: current cache : [ 4 : 4 , 3 : 3 ]
// After: current cache : [ 4 : 8 , 3 : 3 ]
// Test Result: Pass

// Running test: Invalid command format. key = dog cat dog value = abc
// Before: current cache : [ 4 : 8 , 3 : 3 ]
// current cache : [ 4 : 8 , 3 : 3 ]
// Value not accept string.
// After: current cache : [ 4 : 8 , 3 : 3 ]
// Test Result: Pass