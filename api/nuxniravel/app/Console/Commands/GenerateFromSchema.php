<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class GenerateFromSchema extends Command
{
    protected $signature = 'generate:from-schema';
    protected $description = 'Generate Models and Migrations from schema.json';

    public function handle()
    {
        $schemaPath = base_path('schema.json');
        if (!File::exists($schemaPath)) {
            $this->error('schema.json not found!');
            return;
        }

        $schema = json_decode(File::get($schemaPath), true);
        $modelsPath = app_path('Models');
        $migrationsPath = database_path('migrations/generated');

        if (!File::exists($migrationsPath)) {
            File::makeDirectory($migrationsPath, 0755, true);
        }

        foreach ($schema as $tableName => $columns) {
            $modelName = Str::studly(Str::singular($tableName));
            
            // Skip existing critical models to avoid overwriting custom logic
            if (in_array($modelName, ['User', 'UserProfile', 'Role'])) {
                $this->info("Skipping existing model: $modelName");
                continue;
            }

            $this->generateModel($modelName, $tableName, $columns, $modelsPath);
            $this->generateMigration($tableName, $columns, $migrationsPath);
        }

        $this->info('Generation completed!');
    }

    private function generateModel($modelName, $tableName, $columns, $path)
    {
        $fillable = [];
        $casts = [];
        $dates = [];

        foreach ($columns as $column => $type) {
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) continue;
            
            $fillable[] = "'$column'";

            if (in_array($type, ['json', 'array'])) {
                $casts[] = "'$column' => 'array'";
            } elseif (in_array($type, ['boolean', 'bool', 'tinyint'])) {
                 // Heuristic: tinyint is often boolean in Laravel
                $casts[] = "'$column' => 'boolean'";
            } elseif (in_array($type, ['date'])) {
                $casts[] = "'$column' => 'date'";
            } elseif (in_array($type, ['datetime', 'timestamp'])) {
                $casts[] = "'$column' => 'datetime'";
            } elseif (in_array($type, ['int', 'integer', 'bigint', 'smallint'])) {
                $casts[] = "'$column' => 'integer'";
            } elseif (in_array($type, ['float', 'double', 'decimal'])) {
                $casts[] = "'$column' => 'decimal:2'";
            }
        }

        $fillableStr = implode(",\n        ", $fillable);
        $castsStr = implode(",\n        ", $casts);

        $content = "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Factories\HasFactory;\nuse Illuminate\Database\Eloquent\Model;\n\nclass $modelName extends Model\n{\n    use HasFactory;\n\n    protected \$table = '$tableName';\n\n    protected \$fillable = [\n        $fillableStr\n    ];\n\n    protected \$casts = [\n        $castsStr\n    ];\n}\n";

        File::put("$path/$modelName.php", $content);
        $this->info("Generated Model: $modelName");
    }

    private function generateMigration($tableName, $columns, $path)
    {
        $schemaContent = "";
        foreach ($columns as $column => $type) {
            if ($column === 'id') {
                $schemaContent .= "            \$table->id();\n";
                continue;
            }
            if ($column === 'created_at') {
                $schemaContent .= "            \$table->timestamps();\n";
                continue;
            }
            if ($column === 'updated_at') continue; // Handled by timestamps

            $method = 'string';
            $args = "'$column'";
            
            if (str_contains($type, 'int')) $method = 'integer';
            if ($type === 'bigint') $method = 'bigInteger';
            if ($type === 'text') $method = 'text';
            if ($type === 'longtext') $method = 'longText';
            if ($type === 'date') $method = 'date';
            if ($type === 'datetime') $method = 'dateTime';
            if ($type === 'timestamp') $method = 'timestamp';
            if ($type === 'boolean' || $type === 'tinyint') $method = 'boolean';
            if ($type === 'json') $method = 'json';
            if ($type === 'decimal') $method = 'decimal';
            if ($type === 'double') $method = 'double';
            if ($type === 'float') $method = 'float';

            $schemaContent .= "            \$table->$method($args)->nullable();\n";
        }

        $className = 'Create' . Str::studly($tableName) . 'Table';
        $fileName = date('Y_m_d_His') . '_create_' . $tableName . '_table.php';

        $content = "<?php\n\nuse Illuminate\Database\Migrations\Migration;\nuse Illuminate\Database\Schema\Blueprint;\nuse Illuminate\Support\Facades\Schema;\n\nreturn new class extends Migration\n{\n    public function up()\n    {\n        Schema::create('$tableName', function (Blueprint \$table) {\n$schemaContent        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('$tableName');\n    }\n};\n";

        File::put("$path/$fileName", $content);
        $this->info("Generated Migration: $fileName");
    }
}
