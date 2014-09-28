<?php
/*
This file is part of ComfySQL
<https://github.com/csirmaz/ComfySQL>

Copyright (c) 2014 Elod Csirmaz

The MIT License (MIT)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
   ComfySQL is a PHP convenience class for MySQL operations, based on mysqli.

   Please see README.md for details.
*/

class ComfySQL {

   private $h; /*< mysqli object */

   /** $CS = new ComfySQL(DBHOST, DBUSERNAME, DBPASSWORD, DBNAME); */
   public function __construct($host, $username, $password, $database) {
      $this->h = new mysqli($host, $username, $password, $database);
      if($this->h->connect_errno) {
         throw new ComfySQL_Exception($this->h->connect_errno, $this->h->connect_error, 'connect');
      }
   }

   public function __destruct() {
      // Kill the thread
      if(!$this->h->kill($this->h->thread_id)) {
         throw new ComfySQL_Exception($this->h->errno, $this->h->error, 'db/kill(close)');
      }
      if(!$this->h->close()) {
         throw new ComfySQL_Exception($this->h->errno, $this->h->error, 'db/close');
      }
   }

   /** Performs a query with a result set, and returns a single value, or null if no row matched. */
   /** $value = $CS->dbgetsingle("select count(*) from Users"); */
   /** $value = $CS->dbgetsingle("select count(*) from Users where ID > ?", array(12)); */
   public function dbgetsingle($query, $args = null) {
      $data = $this->h->query($this->encode($query, $args), MYSQLI_STORE_RESULT);
      if($data === false) {
         throw new ComfySQL_Exception($this->h->errno, $this->h->error, $query);
      }

      $out = $data->fetch_row();
      $data->free();
      return ($out === null ? null : $out[0]);
   }

   /** Performs a query with a result set, and returns a single row as an associative array,
    * or null if no row matched.
    */
   /** $row = $CS->dbgetrow("select * from Users where ID=?", array(12)); */
   public function dbgetrow($query, $args = null) {
      $data = $this->h->query($this->encode($query, $args), MYSQLI_STORE_RESULT);
      if($data === false) {
         throw new ComfySQL_Exception($this->h->errno, $this->h->error, $query);
      }

      $row = $data->fetch_assoc();
      $data->free();
      return $row;
   }

   /** Performs a query with a result set, and returns all rows as associative arrays. */
   /** $rows = $CS->dbgetall("select * from Users where Surname=? and Firstname=?", array("Smith", "John")); */
   public function dbgetall($query, $args = null) {
      $data = $this->h->query($this->encode($query, $args), MYSQLI_STORE_RESULT);
      if($data === false) {
         throw new ComfySQL_Exception($this->h->errno, $this->h->error, $query);
      }

      $out = array();
      while($row = $data->fetch_assoc()) {
         $out[] = $row;
      }
      $data->free();
      return $out;
   }

   /** Gets rows with a callback, called with an associative array. Return false from the callback to stop. the loop. */
   /** $CS->dbgetcb("select count(*) from Users where ID in (?)", array(array(1,2,3,4)), function($r){
    *     print($r['ID'] . "\n");
    *     // Return false to stop the loop.
    *  });
    */
   public function dbgetcb($query, $args = null, $callback) {
      $data = $this->h->query($this->encode($query, $args), MYSQLI_STORE_RESULT);
      if($data === false) {
         throw new ComfySQL_Exception($this->h->errno, $this->h->error, $query);
      }

      while($row = $data->fetch_assoc()) {
         if($callback($row) === false) {
            break;
         }
      }
      $data->free();
      return;
   }

   /** Performs a query without a result set. */
   /** $CS->dbdo("update Users set Active=? where ID in (?)", array(1, array(2,5,9))); */
   public function dbdo($query, $args = null, $returnaffectedrows = false) {
      if($this->h->query($this->encode($query, $args), MYSQLI_STORE_RESULT) === false) {
         throw new ComfySQL_Exception($this->h->errno, $this->h->error, $query);
      }
   }

   /** Returns the number of rows affected by the previous query. */
   public function get_affected_rows() {
      return $this->h->affected_rows;
   }

   /** Returns the ID generated by the previous insert */
   public function get_insert_id() {
      return $this->h->insert_id;
   }

   // Substitute ?s in the query with "<quoted value>" if the Nth value in $args is a primitive,
   // or "<quoted value>","<quoted value>",... if it is an array.
   /* It makes no sense to use prepare, as one needs to bind both the parameters and
   the returned values to variables, which will act as magic variables, changing their
   values whenever fetch() is called. One needs extra arrays to hold references to the
   values and the output variables, the reflection class to call bind_param and
   bind_result, etc.
   */
   private function encode($query, $args) {

      if($args === null) {
         return $query;
      }

      $p = 0;
      $o = '';
      return preg_replace_callback(
         '/\?/',
         function ($matches) use (&$p, $args) {
            $v = $args[$p++];
            if(is_array($v)) {
               return implode(
                  ',',
                  array_map(function ($e) { return '"' . $this->h->real_escape_string($e) . '"'; }, $v)
               );
            }
            return '"' . $this->h->real_escape_string($v) . '"';
         },
         $query
      );
   }

}

class ComfySQL_Exception extends Exception {

   private $errno;
   private $errstr;

   public function __construct($errno, $errstr, $context) {
      $this->errno = $errno;
      $this->errstr = $errstr;
      $msg = '<' . $errno . '==' . $errstr . '> [[' . $context . ']]';
      parent::__construct($msg);
   }

   public function errno() {
      return $this->errno;
   }

   public function errstr() {
      return $this->errstr;
   }

   public function context() {
      return $this->context;
   }

}

?>