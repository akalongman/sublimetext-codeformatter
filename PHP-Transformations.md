### AddMissingParentheses
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
```

$a = new SomeClass;

```
</td>
<td>
```

$a = new SomeClass();

```
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
```

$a = join(',', $arr);
die("done");

```
</td>
<td>
```

$a = implode(',', $arr);
exit("done");

```
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
```

$a = [
  1 => 1,
  22 => 22,
  333 => 333,
];

```
</td>
<td>
```

$a = [
  1   => 1,
  22  => 22,
  333 => 333,
];

```
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
```


$a = 1; // Comment 1
$bb = 22;  // Comment 2
$ccc = 333;  // Comment 3

```
</td>
<td>
```

$a = 1;      // Comment 1
$bb = 22;    // Comment 2
$ccc = 333;  // Comment 3

```
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
```

$a = 1;
$bb = 22;
$ccc = 333;

```
</td>
<td>
```

$a   = 1;
$bb  = 22;
$ccc = 333;

```
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
```

$a = [
  1 => 1,
  22 => 22,

  333 => 333,
  4444 => 4444,
];

```
</td>
<td>
```

$a = [
  1  => 1,
  22 => 22,

  333  => 333,
  4444 => 4444,
];


```
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
```

function a(
  TypeA $a,
  TypeBB $bb,
  TypeCCC $ccc = array(),
  TypeDDDD $dddd,
  TypeEEEEE $eeeee
){
  noop();
}

```
</td>
<td>
```

function a(
  TypeA     $a,
  TypeBB    $bb,
  TypeCCC   $ccc = array(),
  TypeDDDD  $dddd,
  TypeEEEEE $eeeee
){
  noop();
}

```
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
```

if ($a) {

}

```
</td>
<td>
```

if ($a)
{

}

```
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
```

$a = new SomeClass()

```
</td>
<td>
```

$a = new SomeClass();

```
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
```

class A {
  const constant = 1;
  function b(){
    A::constant;
  }
}

```
</td>
<td>
```

class A {
  const constant = 1;
  function b(){
    self::constant;
  }
}

```
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
```

class A {
  const constant = 1;
  function b(){
    A::constant;
  }
}

```
</td>
<td>
```

class A {
  const constant = 1;
  function b(){
    static::constant;
  }
}

```
</td>
</tr>
</table>


### ConvertOpenTagWithEcho
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
```
<?="Hello World?>

```
</td>
<td>
```
<?php echo "Hello World ?>

```
</td>
</tr>
</table>


### DoubleToSingleQuote
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
```

$a = "";

```
</td>
<td>
```

$a = '';

```
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
```

namespace NS1;
class A {
}

```
</td>
<td>
```

namespace NS1 {
  class A {
  }
}

```
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
```

class A {
  function a(Someclass $a) {
    return 1;
  }
}

```
</td>
<td>
```

class A {
  /**
   * @param Someclass $a
   * @return int
   */
  function a(Someclass $a) {
    return 1;
  }
}

```
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
```

$a = ($b)
? $c
: $d
;

```
</td>
<td>
```

$a = ($b)
  ? $c
  : $d
;

```
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
```

$a = join(',', $arr);

```
</td>
<td>
```

$a = implode(',', $arr);

```
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
```

$a = [$a, $b];

```
</td>
<td>
```

$b = array($b, $c);

```
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
```

if($a){

} else if($b) {

}

```
</td>
<td>
```

if($a){

} elseif($b) {

}

```
</td>
</tr>
</table>


### MergeNamespaceWithOpenTag
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
```
<?php

namespace A;
?>
```
</td>
<td>
```
<?php
namespace A;
?>
```
</td>
</tr>
</table>


### NoSpaceAfterPHPDocBlocks
<table>
<tr>
<td>Before</td>
<td>After</td>
</tr>
<tr>
<td>
```

/**
 * @param int $myInt
 */

function a($myInt){
}

```
</td>
<td>
```

/**
 * @param int $myInt
 */
function a($myInt){
}

```
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
```

use C;
use B;

class D {
  function f() {
    new B();
  }
}

```
</td>
<td>
```

use B;
use C;

class D {
  function f() {
    new B();
  }
}

```
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
```

use C;
use B;

class D {
  function f() {
    new B();
  }
}

```
</td>
<td>
```

use B;

class D {
  function f() {
    new B();
  }
}

```
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
```

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

```
</td>
<td>
```

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

```
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
```

/**
 * @param int $a
 * @param int $b
 * @return int
 */
function abc($a = 10, $b = 20, $c) {

}

```
</td>
<td>
```

/**
 * @param int $a
 * @param int $b
 * @return int
 */
function abc(int $a = 10, int $b = 20, $c): int {

}

```
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
```

/**
 * some description.
 * @param array $b
 * @param LongTypeName $c
 */
function A(array $b, LongTypeName $c) {
}

```
</td>
<td>
```

/**
 * some description.
 * @param array        $b
 * @param LongTypeName $c
 */
function A(array $b, LongTypeName $c) {
}

```
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
```

// PSR2 Mode - From
function a()
{}

```
</td>
<td>
```

function a() {}

```
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
```

function a($a, $b, $c)
{}

```
</td>
<td>
```

function a(
  $a,
  $b,
  $c
) {}

```
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
```

$aaaaa->b
->c;

```
</td>
<td>
```

$aaaaa->b
      ->c;

```
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
```

switch ($a) {
case 1:
  echo 'a';
}

```
</td>
<td>
```

switch ($a) {
  case 1:
    echo 'a';
}

```
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
```

require_once("file.php");

```
</td>
<td>
```

require_once "file.php";

```
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
```

function xxx() {
    // code
};

```
</td>
<td>
```

function xxx() {
    // code
}

```
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
```

namespace NS1;
use \B;
use \D;

new B();
new D();

```
</td>
<td>
```

namespace NS1;
use B;
use D;

new B();
new D();

```
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
```

if ($a and $b or $c) {...}

```
</td>
<td>
```

if ($a && $b || $c) {...}

```
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
```

is_null($a);

```
</td>
<td>
```

null === $a;

```
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
```

function a(){
  return null;
}

```
</td>
<td>
```

function a(){
  return;
}

```
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
```

echo array();

```
</td>
<td>
```

echo [];

```
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
```

if($a) echo array();

```
</td>
<td>
```

if($a) {
  echo array();
}

```
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
```

if ($a) {

}
if ($b) {

}

```
</td>
<td>
```

if ($a) {

}

if ($b) {

}

```
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
```

if (!true) foo();

```
</td>
<td>
```

if ( ! true) foo();

```
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
```

class A {
  function b(){

  }
  function c(){

  }
}

```
</td>
<td>
```

class A {
  function b(){

  }

  function c(){

  }

}

```
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
```

if($a){
} elseif($b) {
}

```
</td>
<td>
```

if($a){
} else if($b) {
}

```
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
```

array_search($needle, $haystack);
base64_decode($str);
in_array($needle, $haystack);

array_keys($arr);
mb_detect_encoding($arr);

array_keys($arr, [1]);
mb_detect_encoding($arr, 'UTF8');

```
</td>
<td>
```

array_search($needle, $haystack, true);
base64_decode($str, true);
in_array($needle, $haystack, true);

array_keys($arr, null, true);
mb_detect_encoding($arr, null, true);

array_keys($arr, [1], true);
mb_detect_encoding($arr, 'UTF8', true);

```
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
```

if($a == $b){}
if($a != $b){}

```
</td>
<td>
```

if($a === $b){}
if($a !== $b){}

```
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
```

$a = [$a, $b, ];
$b = array($b, $c, );

```
</td>
<td>
```

$a = [$a, $b];
$b = array($b, $c);

```
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
```

class A {

  protected $a;
}

```
</td>
<td>
```

class A {
  protected $a;
}

```
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
```

for ($a = 0; $a < 10; $a++){

  if($a){

    // do something
  }
}

```
</td>
<td>
```

for ($a = 0; $a < 10; $a++){
  if($a){
    // do something
  }
}

```
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
```

$a = [$a, $b];
$b = array($b, $c);

```
</td>
<td>
```

$a=[$a,$b];$b=array($b,$c);

```
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
```

for ($a = 0; $a < 10; $a++){

  if($a){

    // do something
  }

}

```
</td>
<td>
```

for ($a = 0; $a < 10; $a++){
  if($a){
    // do something
  }
}

```
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
```

$a = 'a' . 'b';
$a = 'a' . 1 . 'b';

```
</td>
<td>
```

$a = 'a'.'b';
$a = 'a'. 1 .'b';

```
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
```

$var = ereg("[A-Z]", $var);
$var = eregi_replace("[A-Z]", "", $var)
$var = spliti("[A-Z]", $var);

```
</td>
<td>
```

$var = preg_match("/[A-Z]/Di", $var);
$var = preg_replace("/[A-Z]/Di", "", $var);
$var = preg_split("/[A-Z]/Di", $var);

```
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
```

class A {
  function A(){

  }
}

```
</td>
<td>
```

class A {
  function __construct(){

  }
}

```
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
```

if($a == 1){

}

```
</td>
<td>
```

if(1 == $a){

}

```
</td>
</tr>
</table>
