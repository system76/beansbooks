# Beans - Structure and Design

## Class Structure

All classes in beans inherit from the Beans class, which defines some basic 
configuration options for the application. Beyond that, classes extend in
the following order: entry point, action, specific action.

For example:

* Account
    * Search

If at any point a sub-class necessary for that entry point is required, the order
starts all over again.

* Account
    * Type
        * Search

Most entry points contain, at minimum, the following actions:

* Search - Returns a list with minimal details of the entry point.
* Lookup - Returns a single element ( by ID ) with a high level of detail.
* Create - Create said element type.
* Update - Update the properties on said element type.
* Cancel (or Delete) - Either removes the element ( and it's related 
elements ) or marks them as cancelled.

## Interfacing with Classes - Result Objects

All class actions should be used in a two-step process, the action is constructed
and then executed.  For example, if we wanted to get the Chart of Accounts:

> $account_chart = new Beans_Account_Chart();
> $account_chart_result = $account_chart->execute();

All result objects have the following base properties:

* success - TRUE on success, FALSE on error
* error - The error message, empty if no error.
* data - The resulting data object.

The general rule for result objects is that anything with properties is an object, 
and any list of objects is an array. For example - the following code:

    $account_type_search = new Beans_Account_Type_Search();
    $account_type_search_result = $account_type_search->execute();
    print_r($account_type_search_result);

Would produce this output:

    stdClass Object 
    ( 
        [success] => 1 
        [error] =>  
        [data] => stdClass Object 
            ( 
                [total_results] => 6 
                [account_types] => Array 
                    (
                        [0] => stdClass Object 
                            ( 
                                [id] => 1 
                                [name] => Asset 
                                [code] => asset 
                                [table_sign] => -1 
                            ) 
    
                        [1] => stdClass Object 
                            ( 
                                [id] => 2 
                                [name] => Liability 
                                [code] => liability 
                                [table_sign] => 1 
                            
## Interfacing with Classes - Request Objects

All actions that require information take a single $data parameter, which follows 
the same pattern as results, objects have properties, arrays have elements. Let's
say that you wanted to search account transactions, and were requesting the third 
page using a page size of 10:

    $data = new stdClass;
    $data->page_size = 10;
    $data->page = 3;
    $account_transaction_search = new Beans_Account_Transaction_Search($data);
    $account_transaction_search_result = $account_transaction_search->execute();

You could of course create the request object inline as well (as you'll frequently 
find within the application as is standard).

    $account_transaction_search = new Beans_Account_Transaction_Search((object)array(
    	"page_size" => 10,
    	"page" => 3
    ));
    $account_transaction_search_result = $account_transaction_search->execute();

Unless it is not feasible (or easily readable), you should always create your request 
objects inline by appropriately casting arrays to objects.

## Naming Conventions and Models

All of the business objects (models) are driven by Kohana's ORM module, which 
depends on Kohana's Database module.  At some point this may be migrated with
the goal of de-coupling from Kohana as a framework, but at this point in time
it serves the purposes of the application without too many issues.

The following is a general map of model attributes to their purpose:

* code - internal reference ID to the user.
* reference - external reference ID to the user's customer or vendor.
* alt_reference - additional external reference, to be used if reference is taken.
* amount - the specific decimal value assigned to this model.
* total - the sum of all children and this model's amount.
* balance - the unpaid/unreconciled total for this model.

For example, the following diagrams how these are used in the use case of a
customer invoice.

    form
    ->code = Internal Invoice Number
    ->reference = E-Commerce Order Number (viewable by user)
    ->alt_reference = Customer PO Number (in their system)
    ->amount = 0
    ->total = 125

        form_line
        ->amount = 50
        ->total = 75

            form_line_tax
            ->amount = 25

        form_line
        ->amount = 50
        ->total = 50

These fields, when returned by the application to the delivery framework, are 
associated to names that fit the specific use case.  Following the above example, 
the following transformations would take effect:

    form
    ->code : invoice_number
    ->reference : order_number
    ->alt_reference: po_number

Similarly for a vendor purchase order:

    form
    ->code : po_number
    ->reference: so_number
    ->alt_reference: quote_number

The value for code will generally be auto-created if none is provided based on the
type of model and it's ID.  As an example, if a expense had ID 12345, the auto-generated
code would be E12345.

