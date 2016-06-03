### AddMissingParentheses
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = new SomeClass;

</pre>
</td>
<td>
<pre>

$a = new SomeClass();

</pre>
</td>
</tr>
</table>


### AliasToMaster
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = join(',', $arr);
die("done");

</pre>
</td>
<td>
<pre>

$a = implode(',', $arr);
exit("done");

</pre>
</td>
</tr>
</table>


### AlignDoubleArrow
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = [
  1 => 1,
  22 => 22,
  333 => 333,
];

</pre>
</td>
<td>
<pre>

$a = [
  1   => 1,
  22  => 22,
  333 => 333,
];

</pre>
</td>
</tr>
</table>


### AlignDoubleSlashComments
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>


$a = 1; // Comment 1
$bb = 22;  // Comment 2
$ccc = 333;  // Comment 3

</pre>
</td>
<td>
<pre>

$a = 1;      // Comment 1
$bb = 22;    // Comment 2
$ccc = 333;  // Comment 3

</pre>
</td>
</tr>
</table>

### AlignEquals
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = 1;
$bb = 22;
$ccc = 333;

</pre>
</td>
<td>
<pre>

$a   = 1;
$bb  = 22;
$ccc = 333;

</pre>
</td>
</tr>
</table>


### AlignGroupDoubleArrow
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = [
  1 => 1,
  22 => 22,

  333 => 333,
  4444 => 4444,
];

</pre>
</td>
<td>
<pre>

$a = [
  1  => 1,
  22 => 22,

  333  => 333,
  4444 => 4444,
];


</pre>
</td>
</tr>
</table>


### AlignTypehint
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

function a(
  TypeA $a,
  TypeBB $bb,
  TypeCCC $ccc = array(),
  TypeDDDD $dddd,
  TypeEEEEE $eeeee
){
  noop();
}

</pre>
</td>
<td>
<pre>

function a(
  TypeA     $a,
  TypeBB    $bb,
  TypeCCC   $ccc = array(),
  TypeDDDD  $dddd,
  TypeEEEEE $eeeee
){
  noop();
}

</pre>
</td>
</tr>
</table>


### AllmanStyleBraces
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if ($a) {

}

</pre>
</td>
<td>
<pre>

if ($a)
{

}

</pre>
</td>
</tr>
</table>


### AutoSemicolon
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = new SomeClass()

</pre>
</td>
<td>
<pre>

$a = new SomeClass();

</pre>
</td>
</tr>
</table>


### ClassToSelf
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

class A {
  const constant = 1;
  function b(){
    A::constant;
  }
}

</pre>
</td>
<td>
<pre>

class A {
  const constant = 1;
  function b(){
    self::constant;
  }
}

</pre>
</td>
</tr>
</table>


### ClassToStatic
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

class A {
  const constant = 1;
  function b(){
    A::constant;
  }
}

</pre>
</td>
<td>
<pre>

class A {
  const constant = 1;
  function b(){
    static::constant;
  }
}

</pre>
</td>
</tr>
</table>


### ConvertOpenTagWithEcho

##### Before

`<?="Hello World?>`

##### After

`<?php echo "Hello World ?>`


### DoubleToSingleQuote
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = "";

</pre>
</td>
<td>
<pre>

$a = '';

</pre>
</td>
</tr>
</table>


### EncapsulateNamespaces
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

namespace NS1;
class A {
}

</pre>
</td>
<td>
<pre>

namespace NS1 {
  class A {
  }
}

</pre>
</td>
</tr>
</table>


### GeneratePHPDoc
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

class A {
  function a(Someclass $a) {
    return 1;
  }
}

</pre>
</td>
<td>
<pre>

class A {
  /**
   * @param Someclass $a
   * @return int
   */
  function a(Someclass $a) {
    return 1;
  }
}

</pre>
</td>
</tr>
</table>


### IndentTernaryConditions
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = ($b)
? $c
: $d
;

</pre>
</td>
<td>
<pre>

$a = ($b)
  ? $c
  : $d
;

</pre>
</td>
</tr>
</table>


### JoinToImplode
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = join(',', $arr);

</pre>
</td>
<td>
<pre>

$a = implode(',', $arr);

</pre>
</td>
</tr>
</table>


### LongArray
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = [$a, $b];

</pre>
</td>
<td>
<pre>

$b = array($b, $c);

</pre>
</td>
</tr>
</table>


### MergeElseIf
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if($a){

} else if($b) {

}

</pre>
</td>
<td>
<pre>

if($a){

} elseif($b) {

}

</pre>
</td>
</tr>
</table>


### MergeNamespaceWithOpenTag

##### Before

```
<?php

namespace A;
?>
```

##### After

```
<?php
namespace A;
?>
```


### NoSpaceAfterPHPDocBlocks
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

/**
 * @param int $myInt
 */

function a($myInt){
}

</pre>
</td>
<td>
<pre>

/**
 * @param int $myInt
 */
function a($myInt){
}

</pre>
</td>
</tr>
</table>


### OnlyOrderUseClauses
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

use C;
use B;

class D {
  function f() {
    new B();
  }
}

</pre>
</td>
<td>
<pre>

use B;
use C;

class D {
  function f() {
    new B();
  }
}

</pre>
</td>
</tr>
</table>


### OrderAndRemoveUseClauses
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

use C;
use B;

class D {
  function f() {
    new B();
  }
}

</pre>
</td>
<td>
<pre>

use B;

class D {
  function f() {
    new B();
  }
}

</pre>
</td>
</tr>
</table>


### OrganizeClass
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

class A {
  public function d(){}
  protected function b(){}
  private $a = "";
  private function c(){}
  public function a(){}
  public $b = "";
  const B = 0;
  const A = 0;
}

</pre>
</td>
<td>
<pre>

class A {
  const A = 0;

  const B = 0;

  public $b = "";

  private $a = "";

  public function a(){}

  public function d(){}

  protected function b(){}

  private function c(){}
}

</pre>
</td>
</tr>
</table>


### PHPDocTypesToFunctionTypehint
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

/**
 * @param int $a
 * @param int $b
 * @return int
 */
function abc($a = 10, $b = 20, $c) {

}

</pre>
</td>
<td>
<pre>

/**
 * @param int $a
 * @param int $b
 * @return int
 */
function abc(int $a = 10, int $b = 20, $c): int {

}

</pre>
</td>
</tr>
</table>


### PrettyPrintDocBlocks
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

/**
 * some description.
 * @param array $b
 * @param LongTypeName $c
 */
function A(array $b, LongTypeName $c) {
}

</pre>
</td>
<td>
<pre>

/**
 * some description.
 * @param array        $b
 * @param LongTypeName $c
 */
function A(array $b, LongTypeName $c) {
}

</pre>
</td>
</tr>
</table>


### PSR2EmptyFunction
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

// PSR2 Mode - From
function a()
{}

</pre>
</td>
<td>
<pre>

function a() {}

</pre>
</td>
</tr>
</table>

### PSR2IndentWithSpace
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>
    $codeAlignedWithTabs = true
</pre>
</td>
<td>
<pre>
    $codeAlignedWithTabs = false
</pre>
</td>
</tr>
</table>


### PSR2MultilineFunctionParams
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

function a($a, $b, $c)
{}

</pre>
</td>
<td>
<pre>

function a(
  $a,
  $b,
  $c
) {}

</pre>
</td>
</tr>
</table>


### ReindentAndAlignObjOps
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$aaaaa->b
->c;

</pre>
</td>
<td>
<pre>

$aaaaa->b
      ->c;

</pre>
</td>
</tr>
</table>


### ReindentSwitchBlocks
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

switch ($a) {
case 1:
  echo 'a';
}

</pre>
</td>
<td>
<pre>

switch ($a) {
  case 1:
    echo 'a';
}

</pre>
</td>
</tr>
</table>


### RemoveIncludeParentheses
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

require_once("file.php");

</pre>
</td>
<td>
<pre>

require_once "file.php";

</pre>
</td>
</tr>
</table>


### RemoveSemicolonAfterCurly
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

function xxx() {
    // code
};

</pre>
</td>
<td>
<pre>

function xxx() {
    // code
}

</pre>
</td>
</tr>
</table>


### RemoveUseLeadingSlash
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

namespace NS1;
use \B;
use \D;

new B();
new D();

</pre>
</td>
<td>
<pre>

namespace NS1;
use B;
use D;

new B();
new D();

</pre>
</td>
</tr>
</table>


### ReplaceBooleanAndOr
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if ($a and $b or $c) {...}

</pre>
</td>
<td>
<pre>

if ($a && $b || $c) {...}

</pre>
</td>
</tr>
</table>


### ReplaceIsNull
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

is_null($a);

</pre>
</td>
<td>
<pre>

null === $a;

</pre>
</td>
</tr>
</table>


### ReturnNull
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

function a(){
  return null;
}

</pre>
</td>
<td>
<pre>

function a(){
  return;
}

</pre>
</td>
</tr>
</table>


### ShortArray
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

echo array();

</pre>
</td>
<td>
<pre>

echo [];

</pre>
</td>
</tr>
</table>


### SmartLnAfterCurlyOpen
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if($a) echo array();

</pre>
</td>
<td>
<pre>

if($a) {
  echo array();
}

</pre>
</td>
</tr>
</table>


### SpaceAroundControlStructures
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if ($a) {

}
if ($b) {

}

</pre>
</td>
<td>
<pre>

if ($a) {

}

if ($b) {

}

</pre>
</td>
</tr>
</table>


### SpaceAroundExclamationMark
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if (!true) foo();

</pre>
</td>
<td>
<pre>

if ( ! true) foo();

</pre>
</td>
</tr>
</table>


### SpaceBetweenMethods
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

class A {
  function b(){

  }
  function c(){

  }
}

</pre>
</td>
<td>
<pre>

class A {
  function b(){

  }

  function c(){

  }

}

</pre>
</td>
</tr>
</table>


### SplitElseIf
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if($a){
} elseif($b) {
}

</pre>
</td>
<td>
<pre>

if($a){
} else if($b) {
}

</pre>
</td>
</tr>
</table>


### StrictBehavior
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

array_search($needle, $haystack);
base64_decode($str);
in_array($needle, $haystack);

array_keys($arr);
mb_detect_encoding($arr);

array_keys($arr, [1]);
mb_detect_encoding($arr, 'UTF8');

</pre>
</td>
<td>
<pre>

array_search($needle, $haystack, true);
base64_decode($str, true);
in_array($needle, $haystack, true);

array_keys($arr, null, true);
mb_detect_encoding($arr, null, true);

array_keys($arr, [1], true);
mb_detect_encoding($arr, 'UTF8', true);

</pre>
</td>
</tr>
</table>


### StrictComparison
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if($a == $b){}
if($a != $b){}

</pre>
</td>
<td>
<pre>

if($a === $b){}
if($a !== $b){}

</pre>
</td>
</tr>
</table>


### StripExtraCommaInArray
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = [$a, $b, ];
$b = array($b, $c, );

</pre>
</td>
<td>
<pre>

$a = [$a, $b];
$b = array($b, $c);

</pre>
</td>
</tr>
</table>


### StripNewlineAfterClassOpen
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

class A {

  protected $a;
}

</pre>
</td>
<td>
<pre>

class A {
  protected $a;
}

</pre>
</td>
</tr>
</table>


### StripNewlineAfterCurlyOpen
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

for ($a = 0; $a < 10; $a++){

  if($a){

    // do something
  }
}

</pre>
</td>
<td>
<pre>

for ($a = 0; $a < 10; $a++){
  if($a){
    // do something
  }
}

</pre>
</td>
</tr>
</table>


### StripSpaces
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = [$a, $b];
$b = array($b, $c);

</pre>
</td>
<td>
<pre>

$a=[$a,$b];$b=array($b,$c);

</pre>
</td>
</tr>
</table>


### StripSpaceWithinControlStructures
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

for ($a = 0; $a < 10; $a++){

  if($a){

    // do something
  }

}

</pre>
</td>
<td>
<pre>

for ($a = 0; $a < 10; $a++){
  if($a){
    // do something
  }
}

</pre>
</td>
</tr>
</table>


### TightConcat
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$a = 'a' . 'b';
$a = 'a' . 1 . 'b';

</pre>
</td>
<td>
<pre>

$a = 'a'.'b';
$a = 'a'. 1 .'b';

</pre>
</td>
</tr>
</table>


### UpgradeToPreg
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

$var = ereg("[A-Z]", $var);
$var = eregi_replace("[A-Z]", "", $var)
$var = spliti("[A-Z]", $var);

</pre>
</td>
<td>
<pre>

$var = preg_match("/[A-Z]/Di", $var);
$var = preg_replace("/[A-Z]/Di", "", $var);
$var = preg_split("/[A-Z]/Di", $var);

</pre>
</td>
</tr>
</table>


### WrongConstructorName
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

class A {
  function A(){

  }
}

</pre>
</td>
<td>
<pre>

class A {
  function __construct(){

  }
}

</pre>
</td>
</tr>
</table>


### YodaComparisons
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
<pre>

if($a == 1){

}

</pre>
</td>
<td>
<pre>

if(1 == $a){

}

</pre>
</td>
</tr>
</table>
