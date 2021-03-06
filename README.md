# ComfySQL

A PHP convenience class for MySQL operations, based on mysqli.

## Usage

```PHP

// Create a connection
$CS = new ComfySQL(DBHOST, DBUSERNAME, DBPASSWORD, DBNAME);

// Performs a query with a result set, and returns a single value, or null if no row matched.
$value = $CS->dbgetsingle("select count(*) from Users");
$value = $CS->dbgetsingle("select count(*) from Users where ID > ?", array(12));

// Performs a query with a result set, and returns a single row as an associative array,
// or null if no row matched.
$row = $CS->dbgetrow("select * from Users where ID=?", array(12));

// Performs a query with a result set, and returns all rows as associative arrays.
$rows = $CS->dbgetall("select * from Users where Surname=? and Firstname=?", array("Smith", "John"));
foreach($rows => $row) {
   print($row['ID'] . "\n");
}

// Gets rows with a callback, called with an associative array. Return false from the callback 
// to stop the loop.
$CS->dbgetcb("select count(*) from Users where ID in (?)", array(array(1,2,3,4)), function($r){
   print($r['ID'] . "\n");
   // Return false to stop the loop.
});

// Performs a query without a result set.
$CS->dbdo("update Users set Active=? where ID in (?)", array(1, array(2,5,9)));

// Returns the number of rows affected by the previous query.
$NumAffected = $CS->get_affected_rows();

// Returns the ID generated by the previous insert.
$InsertId = $CS->get_insert_id();

```

## Notes

"?" in the queries are substituted for MySQL-quoted values enclosed in double quotes as listed in
the optional array after the query. If the array element is an array itself, the code substitutes
the "?" for a comma-separated list of quoted values.

Use null instead of the array of values if no substitution is necessary.

In case of an error, operations throw a ComfySQL_Exception object. The object has the following
methods:

* errno -- the MySQL error number
* errstr -- the MySQL error string
* context -- the context in which the error occurred, usually the query

## License

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
