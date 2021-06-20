<?
//Short open tag usage

//Undefined class 'NonExistent'
final class InspectionViolator extends NonExistent
{
    //Constant name 'badConstant' doesn't match regex '[A-Z][A-Z_\d]*'
    //PSR-12: Missing visibility definition
    //Constant is never used.
    const badConstant = [];

    //Typed properties are only allowed since PHP 7.4
    //Missing PHPDoc comment for field
    //Property name '$bad_propertie' doesn't match regex '[a-z][A-Za-z\d]*'
    //Typo: In word 'propertie'
    public string $bad_propertie = 1;

    //Constructor is never used.
    public function __construct()
    {
        //Method call is provided 1 parameters, but the method signature uses 0 parameters
        $this->bad_propertie = $this->bad_method('arg');

        //Method '__toString' is not implemented for '\stdClass'
        echo 'one' . new \stdClass();
    }

    //PHPDoc for non-existing argument
    //Return type does not match the declared
    //'Abstract' modifier is not allowed here
    //Method should either have body or be abstract
    //Method name 'bad_method' doesn't match regex '[a-z][A-Za-z\d]*'
    /**
     * @param $nonExistent
     * @return int
     */
    abstract private function bad_method(): void
    {
        //Undefined variable '$argument'
        //Statement has empty body
        if ($argument == 'string') {
        }

        //Expression result is not used anywhere
        isset($argument);

        //A void function must not return a value
        return 5;
    }

    //Undefined class 'InvalidData'
    //Unused private method 'unused'
    //Method owner class is never instantiated OR An instantiation is not reachable from entry points.
    //Missing function's return type declaration
    /**
     * @return int
     * @throws InvalidData
     */
    private function unused()
    {
        return 1;
    }

    //PHPDoc comment doesn't contain all the necessary @throws tags
    //Method owner class is never instantiated OR An instantiation is not reachable from entry points.
    /**
     * @return int
     */
    protected function method(): int
    {
        $arg = [];

        if (isset($arg)) {
            //Unused local variable 'item'. The value of the variable is not used anywhere.
            //Array is always empty at this point
            $item = $arg['item'];

            //Unhandled exception
            throw new \Exception();

        //Unnecessary ;
        };

        //Invalid argument supplied to 'foreach'
        foreach ('x' as $item) {
            echo $item;
        }
        //Missing 'return' statement
    }
}

//Another definition with same name exists in this file
//Class should not extend itself
//Multiple definitions exist for class 'InspectionViolator'
class InspectionViolator extends InspectionViolator
{}