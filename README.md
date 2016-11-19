#Installation

Install via composer - edit your composer.json to require the package.
```
"require": {
    "enigmacsv/dummy-csv": "1.0.*"
}
```
Then run composer update in your terminal to pull it in.
Once this has finished, you will need to add the service provider to the providers 
array in your bootstrap/app.php config as follows:
```
$app->register(Enigmacsv\DummyCsv\DummyCsvServiceProvider::class);
```
###Dummy csv for employee
```
http://{url}/dummy/csv/employee?company_id=1&branch_id=1&take=1000
```
###Dummy csv for employee kintai
```
http://{url}/dummy/csv/kintai?company_id=1&branch_id=1&&take=1000
```
###URL parameters:
1. Employee
    * company_id=1
    * branch_id=1
    * take=1 (default=1000)
    
2. Kintai
    * company_id=1
    * branch_id=1
    * take=1 (default=1000)
    * type=1 (default=1)
    * company_kintai_id=1 (default=random)
