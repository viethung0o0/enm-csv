<?php

namespace Enigmacsv\DummyCsv\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Employee Resource.
 *
 * @Resource("Employee")
 */
class DummyCsvController extends Controller
{

    /**
     * Take employee
     */
    const TAKE_EMPLOYEE = 1000;

    /**
     * Package config
     *
     * @var array
     */
    protected $config;

    /**
     * Deposit type
     *
     * @var array
     */
    public static $depositTypes = [
        self::DEPOSIT_TYPE_NORMAL => "普通",
        self::DEPOSIT_TYPE_CURRENT => "当座",
        self::DEPOSIT_TYPE_SAVING => "貯蓄",
    ];

    /**
     * Contract type
     *
     * @var array
     */
    public static $contractType = [
        self::CONTRACT_TYPE_REGULAR => "正社員",
        self::CONTRACT_TYPE_PART_TIME => "パート・アルバイト",
        self::CONTRACT_TYPE_TEMP_CONTRACT => "派遣・契約社員",
        self::CONTRACT_TYPE_OUTSOURCING_BUSINESS => "外注・業務請負",
    ];

    /**
     * Deposit type is normal
     */
    const DEPOSIT_TYPE_NORMAL = 1;

    /**
     * Deposit type is current
     */
    const DEPOSIT_TYPE_CURRENT = 2;

    /**
     * Deposit type is saving
     */
    const DEPOSIT_TYPE_SAVING = 4;

    /**
     * Contract type is regular employee
     */
    const CONTRACT_TYPE_REGULAR = 1;

    /**
     * Contract type is part time
     */
    const CONTRACT_TYPE_PART_TIME = 2;

    /**
     * Contract type is temporary and contract
     */
    const CONTRACT_TYPE_TEMP_CONTRACT = 3;

    /**
     * Contract type is outsourcing and business
     */
    const CONTRACT_TYPE_OUTSOURCING_BUSINESS = 4;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->config = config('dummy_csv');
    }

    /**
     * Get pattern of company kintai
     *
     * @param int $type Type
     *
     * @return get Pattern
     */
    public function getKintaiPattern(int $type)
    {
        switch ($type) {
            case \App\Models\CompanyKintai::KINTAI_PATTERN_1:
                $pattern = config('define.employees.kintai_format.' . \App\Models\CompanyKintai::KINTAI_PATTERN_1);
                break;
            case \App\Models\CompanyKintai::KINTAI_PATTERN_2:
                $pattern = config('define.employees.kintai_format.' . \App\Models\CompanyKintai::KINTAI_PATTERN_2);
                break;
            case \App\Models\CompanyKintai::KINTAI_PATTERN_3:
                $pattern = config('define.employees.kintai_format.' . \App\Models\CompanyKintai::KINTAI_PATTERN_3);
                $rest = array_splice($pattern, 4);
                for ($i = 1; $i <= 20; $i++) {
                    $pattern['salary_' . $i] = trans('labels.kintai_format.hourly_wage', [
                        'num' => $i
                    ]);
                    $pattern['time_' . $i] = trans('labels.kintai_format.working_hour', [
                        'num' => $i
                    ]);
                }
                $pattern += $rest;
                break;
            default :
                $pattern = null;
        }

        return $pattern;
    }

    /**
     * Export csv kintais
     *
     * @param Request $request Request
     *
     * @return \Illuminate\Http\Response
     */
    public function exportCsvKintai(Request $request)
    {
        try {
            $companyId = $request->get('company_id');
            $branchId = $request->get('branch_id');
            $type = $request->get('type', 1);
            $take = $request->get('take', self::TAKE_EMPLOYEE);
            $companyKintaiId = $request->get('company_kintai_id');
            $faker = \Faker\Factory::create('ja_JP');

            $companyKintai = $this->getCompanyKintai([
                'company_id' => $companyId,
                'type' => $type,
                'id' => $companyKintaiId
            ]);

            $companyKintaiAttribute = $this->getCompanyKintaiAttributes([
                'company_kintai_id' => $companyKintai->id
            ]);

            $employees = $this->getEmployees([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'take' => $take,
                ], ['id', 'code']);

            $data = $this->getContentCsvKintai($faker, [
                'employees' => $employees,
                'company_kintai_attribute' => $companyKintaiAttribute,
                'type' => $type
            ]);
            $headers = array_keys(reset($data));

            return $this->downloadCsv($headers, $data, "test_kintai.csv");
        } catch (\Exception $exc) {
            dd($exc);
            return response()->json([$exc->getMessage(), $exc]);
        }
    }

    /**
     * Get content csv kintai
     *
     * @param Faker $faker Faker
     * @param array $data  Data
     *
     * @return array
     */
    private function getContentCsvKintai($faker, $data)
    {
        $headers = array_keys($data['company_kintai_attribute']);
        $result = [];
        foreach ($data['employees'] as $employee) {
            //get dummy data
            $array = $this->createDummyKintaiData($faker, [
                'employees' => $employee ?? uniqid()
            ]);

            $attribute = array_flip(array_filter($data['company_kintai_attribute']));
            $attributeValue = array_intersect_key($array, $attribute);
            $dontMap = array_diff_key(array_flip($headers), array_flip($attribute));

            $newData = [];
            foreach ($attributeValue as $key => $value) {
                $newData = array_merge($newData, [
                    $attribute[$key] => $value
                ]);
            }
            foreach ($dontMap as $key => $value) {
                $newData = array_merge($newData, [
                    $key => $faker->sentence()
                ]);
            }

            $result[] = $newData;
        }

        return $result;
    }

    /**
     * Get data of employees table
     *
     * @param array $params  Parameters
     * @param array $columns Columns
     *
     * @return aray
     */
    public function getEmployees($params, $columns = ['*'])
    {
        $query = DB::table($this->config['employee_table'])
            ->select($columns)
            ->where('company_id', $params['company_id'])
            ->orderBy('id', 'asc')
            ->take($params['take']);
        
        if (!is_null($params['branch_id'])) {
            $query->where('branch_id', $params['branch_id']);
        }

        return $query->get();
    }

    /**
     * Get data of company kintais table
     *
     * @param array $data    Data
     * @param array $columns Columns
     *
     * @return aray
     */
    private function getCompanyKintai($data, $columns = ['*'])
    {
        $companyKintai = DB::table($this->config['company_kintai_table'])
            ->select($columns)
            ->where('company_id', $data['company_id'])
            ->where('type', $data['type']);

        if (!empty($data['id'])) {
            return $companyKintai->where('id', $data['id'])
                    ->first();
        }
        $companyKintais = $companyKintai->get();
        if ($companyKintais->isEmpty()) {
            throw new \Exception('Type or company kintai id not found');
        }
        return $companyKintais->random();
    }

    /**
     * Get data of company kintai attribute table
     *
     * @param array $data    Data
     * @param array $columns Columns
     *
     * @return aray
     */
    private function getCompanyKintaiAttributes($data, $columns = ['*'])
    {
        return DB::table($this->config['company_kintai_attribute_table'])
                ->select($columns)
                ->where('company_kintai_id', $data['company_kintai_id'])
                ->orderBy(\DB::raw('"order"'), 'asc')
                ->get()
                ->pluck('value', 'attribute')
                ->all();
    }

    /**
     * Download csv
     *
     * @param type $headers  Header csv
     * @param type $data     Content csv
     * @param type $fileName File name
     *
     * @return int Returns the number of characters read from the handle
     *             and passed through to the output.
     */
    protected function downloadCsv($headers, $data, $fileName)
    {
        $writer = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $writer->setDelimiter(",");
        $writer->setNewline("\r\n");
        $writer->insertOne($headers);
        $writer->insertAll($data);
        $writer->output($fileName);
    }

    /**
     * Random number by length
     *
     * @param int $length Limit length
     *
     * @return integer
     */
    protected function randomNumberByLength($length)
    {
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return mt_rand($min, $max);
    }

    /**
     * Create dummy data for kintai
     *
     * @param Faker $faker Faker
     * @param array $data  Data
     *
     * @return array
     */
    protected function createDummyKintaiData($faker, $data)
    {
        $fakerData = [
            'employee_code' => !empty($data['employees']) ? $data['employees']->code : '',
            'updated_date' => $faker->dateTimeBetween('-10 years', '-2 years')->format('Y-m-d'),
            'branch_name' => str_replace(" ", "", $faker->name),
            'employee_name' => str_replace(" ", "", $faker->name),
            'employee_name_kana' => str_replace(" ", "", $faker->kanaName),
            'total_amount_of_salary' => rand(1000, 2000),
            'basic_salary' => rand(200, 500),
            'overtime_payment' => rand(500, 600),
            'working_days' => rand(1, 7),
            'normal_working_hours' => rand(6, 8),
            'overtime_hours' => rand(6, 8),
            'others' => $faker->sentence(),
        ];
        for ($i = 1; $i <= 20; $i++) {
            $fakerData['salary_' . $i] = rand(1, 7);
            $fakerData['time_' . $i] = rand(200, 500);
        }

        return $fakerData;
    }

    /**
     * Get data of branches table
     *
     * @param array $params  Parameters
     * @param array $columns Columns
     *
     * @return Model
     */
    private function getBranch($params, $columns = ['*'])
    {
        return DB::table($this->config['branch_table'])
                ->select($columns)
                ->where('company_id', $params['company_id'])
                ->find($params['branch_id']);
    }

    /**
     * Export csv employee.
     *
     * @param Request $request Request
     *
     * @return \Illuminate\Http\Response
     */
    public function exportCsvEmployee(Request $request)
    {
        try {
            $faker = \Faker\Factory::create('ja_JP');
            $companyId = $request->get('company_id');
            $branchId = $request->get('branch_id');
            $take = $request->get('take', self::TAKE_EMPLOYEE);

            $headers = $this->getEmployeeCsvHeader();
            $branch = $this->getBranch([
                'company_id' => $companyId,
                'branch_id' => $branchId
            ]);

            for ($i = 1; $i <= $take; $i++) {
                $response[] = $this->createDummyEmployeeData($faker, [
                    'branch_id' => $branch->id,
                    'company_id' => $companyId,
                    'employee_id' => $i
                ]);
            }
            return $this->downloadCsv($headers, $response, "test_employee.csv");
        } catch (\Exception $ex) {
            dd($ex);
            return response()->json([$ex->getMessage(), $ex->getTrace()]);
        }
    }

    /**
     * Create dummy data for employee
     *
     * @param Faker $faker  Faker
     * @param array $params Parameters
     *
     * @return array
     */
    protected function createDummyEmployeeData($faker, $params)
    {
        $contractType = \App\Models\Employee::$contractType ?? self::$contractType;
        $depositType = \App\Models\Employee::$depositTypes ?? self::$depositTypes;

        return [
            '社員コード' => 'CPN_' . $params['company_id'] . '_BR_' . $params['branch_id'] . '_EMP_' . $params['employee_id'],
            'メールアドレス' => $faker->unique()->email,
            '氏名（漢字）' => str_replace(" ", "", $faker->name),
            '氏名（カナ）' => str_replace(" ", "", $faker->kanaName),
            '契約種別（従業員属性）' => trans($faker->randomElement($contractType)),
            '契約開始日（入社日）' => $faker->dateTimeBetween('-10 years', '-2 years')->format('Y-m-d'),
            '契約終了日' => $faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d'),
            '月給' => rand(1000, 2000),
            '残業代（時間単位）' => rand(20, 30),
            '時給' => rand(10, 50),
            '日給' => rand(80, 400),
            '振込先金融機関コード' => $this->randomNumberByLength(4),
            '振込先金融機関名称（カナ）' => str_replace(" ", "", $faker->kanaName),
            '振込先営業店コード' => $this->randomNumberByLength(3),
            '振込先営業店名称（カナ）' => str_replace(" ", "", $faker->kanaName),
            '預金種目' => trans($faker->randomElement($depositType)),
            '口座番号' => $this->randomNumberByLength(7),
            '受取人名（カナ）' => str_replace(" ", "", $faker->kanaName),
        ];
    }

    /**
     * Get employee csv headers
     *
     * @return array
     */
    protected function getEmployeeCsvHeader()
    {
        return [
            '社員コード',
            'メールアドレス',
            '氏名（漢字）',
            '氏名（カナ）',
            '契約種別（従業員属性）',
            '契約開始日（入社日）',
            '契約終了日',
            '月給',
            '残業代（時間単位）',
            '時給',
            '日給',
            '振込先金融機関コード',
            '振込先金融機関名称（カナ）',
            '振込先営業店コード',
            '振込先営業店名称（カナ）',
            '預金種目',
            '口座番号',
            '受取人名（カナ）',
        ];
    }
}
