# Contributing to BeansBooks

BeansBooks was created with the goal of re-inventing accounting software.  
We wanted to create a modern and powerful tool that enables small businesses 
to take advantage of the same open source ecosystem that many of us were 
already using for the rest of our software stack.  With that in mind, 
BeansBooks public development will be done in public in the hope that many 
other developers will join in contributing to this project to support the 
many businesses and organizations that will benefit from its progress.

## Getting Started

BeansBooks is a web platform built primarily on PHP.  The front-end of the 
tool-suite is developed using modern web technologies ( i.e. CSS and Javascript 
) and attempts to adhere to most accepted best practices.  If you're interesting 
in contributing code to BeansBooks, it is highly suggested that you be familiar 
with PHP object oriented design patterns ( specifically, Interactor / Use-Case 
), jQuery, and the standard LAMP stack.

Beyond simply submitting code, we're intested in users who are willing to submit 
feedback, report bugs, and make feature suggestions to be considered in our 
development roadmap.  One thing that we have learned in the development of 
BeansBooks thus far is that no one set of users has all of the best ideas when it 
comes to software development.  The project will only grow stronger with more 
interested parties contributing thoughtful criticism and unique solutions to the 
problems at hand.

## License and Contribution Agreement

The BeansBooks license can be found in the root of the project code repository ( 
https://github.com/system76/beansbooks/blob/master/BEANSBOOKS.LICENSE.md ) and 
includes important details regarding contributing to the project.  While it is 
highly encouraged that you read the entire license, it is especially important 
to read through the section titled __3. Contributions__, as your contributions 
will be bound to this agreement by simply submitting them to the project.

## Repository Structure

The Git repository features two main branches: master and develop.  The master 
branch ONLY hosts stable and released code, and is tagged at each release point 
for convenience.  If you grab the latest commit to that branch, you will in turn 
have the latest stable release.  The develop branch will accept all pull requests 
for features.  It will generally be tagged at release candidate points for the 
stable releases that should eventually make their way into the master branch.  
The format for these tags is __vX.YrcN__, where X.Y represents the major.minor 
version number to be released, and N is the revision of the release candidate.  
The corresponding master release tags will be formatted as __vX.Y__.  If there 
is a hotfix applied for a critical bug in between minor releases, it will be 
tagged as __vX.Y.Z__.  The only case where a pull request may be made directly 
to the master branch without first going through develop is for a hotfix.

## Submitting Issues

Any feature request or bug should be submitted by creating an issue on the 
project page.  If you feel comfortable doing so, please appropriately tag it 
as either a Bug or Feature.  Feature Requests are evaluated by the project 
maintainers and will be included on the next appropriate milestone for development 
if accepted.  Issues are not an appropriate means of asking questions with regards 
to accounting or how to use BeansBooks in your own use case - they are strictly 
meant to support the development of the project.

If you are submitting a bug with the code or interface, please include as much 
information as possible ( screenshots, logs, links to code, etc. ) along with a 
details description of the exact steps to reproduce the issue.

## Submitting Pull Requests

All pull requests should be submitted to the develop branch of the project and 
should reference at least one issue.  Any pull request that does not match up 
with the code guidelines, reference an issue, or have proper formatted will be 
asked to be fixed before being accepted.

## Development Cycle

Milestones will be used to track development towards a release, and each will 
have a set of issues representing the improvements made within that release.  
In order to address an issue for a release, you should fork the develop branch, 
add the feature, and then submit a pull request back towards the development 
branch referencing the applicable issue.  Once all of the issues for a 
milestone have been addressed or moved to a later milestone, an RC version for 
that release will be tagged in the develop branch.  The develop branch will not 
accept any more pull requests until it has successfully tested and merged the 
final RC back to the master branch.

## Code Format and Structure

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

### Interfacing with Classes - Result Objects

All class actions should be used in a two-step process, the action is constructed
and then executed.  For example, if we wanted to get the Chart of Accounts:

    $account_chart = new Beans_Account_Chart();
    $account_chart_result = $account_chart->execute();

All result objects have the following base properties:

* success - TRUE on success, FALSE on error
* error - The error message, empty if no error.
* auth_error - The error message if tied to credentials or an invalid role.
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
                            
### Interfacing with Classes - Request Objects

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

### Naming Conventions and Models

All of the business objects (models) are driven by Kohana's ORM module, which 
depends on Kohana's Database module.  At some point this may be migrated with
the goal of de-coupling from Kohana as a framework, but at this point in time
it serves the purposes of the application without too many issues.

The following is a general map of model attributes to their purpose:

* code - internal reference ID to the user.
* reference - external reference ID to the user's customer or vendor.
* alt_reference - additional external reference, to be used if reference is taken.
* aux_reference - yet another external reference
* amount - the specific decimal value assigned to this model.
* total - the sum of all children and this model's amount.
* balance - the unpaid/unreconciled total for this model.

For example, the following diagrams how these are used in the use case of a
customer sale.

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
