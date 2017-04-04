<?php

namespace Songshenzong\Log\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Songshenzong\Log\SongshenzongLog;

class LogController extends BaseController
{

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    public $app;


    /**
     * @var string\
     */
    protected $table = 'songshenzong_logs';


    /**
     * CurrentController constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this -> app = $app;
    }


    /**
     * Drop Table.
     *
     * @return bool
     */
    public function dropTable()
    {
        if (\DB ::statement("DROP TABLE IF EXISTS {$this->table};")) {
            return response() -> json(['status_code' => 200]);
        }
        return response() -> json(['status_code' => 500]);
    }


    /**
     * Create table.
     */
    public function createTable()
    {
        $createTable = <<<"HEREDOC"
CREATE TABLE IF NOT EXISTS {$this -> table}
(
  id         INT UNSIGNED     NOT NULL  AUTO_INCREMENT  PRIMARY KEY,
  data       LONGTEXT         NOT NULL,
  utime      VARCHAR(100)     NOT NULL,
  uri        VARCHAR(300)     NOT NULL,
  ip         VARCHAR(50)      NOT NULL,
  method     VARCHAR(10)      NOT NULL,
  created_at TIMESTAMP        NULL,
  updated_at TIMESTAMP        NULL
);
HEREDOC;


        if (\DB ::statement($createTable)) {
            \DB ::statement("CREATE INDEX {$this->table}_created_at_index ON {$this->table} (created_at);");
            \DB ::statement("CREATE INDEX {$this->table}_ip_index ON {$this->table} (ip);");
            \DB ::statement("CREATE INDEX {$this->table}_method_index ON {$this->table} (method);");
            \DB ::statement("CREATE INDEX {$this->table}_uri_index ON {$this->table} (uri);");
            \DB ::statement("CREATE INDEX {$this->table}_utime_index ON {$this->table} (utime);");
            return response() -> json(['status_code' => 200]);
        }
        return response() -> json(['status_code' => 500]);

    }


    /**
     * @param null $id
     * @param null $last
     *
     * @return mixed
     */
    public function getData($id)
    {
        $log = SongshenzongLog ::find($id);
        return response() -> json($log);
    }


    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList()
    {
        $list = SongshenzongLog :: orderBy('created_at', 'desc')
                                -> paginate(\request() -> per_page??23)
                                -> appends(\request() -> all())
                                -> toArray();


        return response() -> json($list);
    }


    /**
     * Index
     */
    public function index()
    {
        $file = __DIR__ . '/../Views/index.html';
        echo file_get_contents($file);
        exit;
    }


    /**
     * @return \Illuminate\Http\JsonResponse|int
     */
    public function destroy()
    {
        if (\request() -> has('id')) {
            return SongshenzongLog ::destroy(\request() -> id);
        }

        SongshenzongLog ::where('id', '!=', 0) -> delete();

        return $this -> getList();

    }
}
