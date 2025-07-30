<?php


require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 1. new Spreadsheet()                  ← cria planilha na memória
 * 2. setCellValue(..., ...)            ← preenche planilha na memória
 * 3. new Xlsx($spreadsheet)            ← prepara para salvar
 *  4. ->save('teste_2.xlsx')            ← grava no HD de verdade!

 */

$alunos = [
    [
        'id' => 1,
        'nome' => 'Maria Oliveira',
        'idade' => 17,
        'sexo' => 'Feminino',
        'email' => 'maria.oliveira@email.com',
        'telefone' => '(21) 98765-4321',
        'endereco' => 'Av. Brasil, 456',
        'cidade' => 'Rio de Janeiro',
        'estado' => 'RJ',
        'cep' => '22040-002',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 2,
        'nome' => 'Carlos Santos',
        'idade' => 16,
        'sexo' => 'Masculino',
        'email' => 'carlos.santos@email.com',
        'telefone' => '(11) 91234-5678',
        'endereco' => 'Rua das Acácias, 789',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'cep' => '04567-890',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 3,
        'nome' => 'Ana Beatriz',
        'idade' => 15,
        'sexo' => 'Feminino',
        'email' => 'ana.beatriz@email.com',
        'telefone' => '(31) 99876-5432',
        'endereco' => 'Praça Central, 123',
        'cidade' => 'Belo Horizonte',
        'estado' => 'MG',
        'cep' => '30123-456',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 4,
        'nome' => 'João Pedro',
        'idade' => 16,
        'sexo' => 'Masculino',
        'email' => 'joao.pedro@email.com',
        'telefone' => '(41) 98765-4321',
        'endereco' => 'Rua das Palmeiras, 321',
        'cidade' => 'Curitiba',
        'estado' => 'PR',
        'cep' => '80010-020',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 5,
        'nome' => 'Larissa Fernandes',
        'idade' => 17,
        'sexo' => 'Feminino',
        'email' => 'larissa.fernandes@email.com',
        'telefone' => '(51) 91234-9876',
        'endereco' => 'Av. Central, 555',
        'cidade' => 'Porto Alegre',
        'estado' => 'RS',
        'cep' => '90040-030',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 6,
        'nome' => 'Felipe Costa',
        'idade' => 15,
        'sexo' => 'Masculino',
        'email' => 'felipe.costa@email.com',
        'telefone' => '(71) 99876-1234',
        'endereco' => 'Rua do Sol, 777',
        'cidade' => 'Salvador',
        'estado' => 'BA',
        'cep' => '40020-040',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 7,
        'nome' => 'Beatriz Almeida',
        'idade' => 16,
        'sexo' => 'Feminino',
        'email' => 'beatriz.almeida@email.com',
        'telefone' => '(85) 98765-2345',
        'endereco' => 'Rua das Flores, 888',
        'cidade' => 'Fortaleza',
        'estado' => 'CE',
        'cep' => '60040-050',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 8,
        'nome' => 'Rafael Martins',
        'idade' => 17,
        'sexo' => 'Masculino',
        'email' => 'rafael.martins@email.com',
        'telefone' => '(19) 91234-8765',
        'endereco' => 'Av. das Nações, 999',
        'cidade' => 'Campinas',
        'estado' => 'SP',
        'cep' => '13010-060',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 9,
        'nome' => 'Juliana Rodrigues',
        'idade' => 16,
        'sexo' => 'Feminino',
        'email' => 'juliana.rodrigues@email.com',
        'telefone' => '(27) 98765-3456',
        'endereco' => 'Rua Verde, 111',
        'cidade' => 'Vitória',
        'estado' => 'ES',
        'cep' => '29040-070',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 10,
        'nome' => 'Gustavo Lima',
        'idade' => 15,
        'sexo' => 'Masculino',
        'email' => 'gustavo.lima@email.com',
        'telefone' => '(62) 91234-9876',
        'endereco' => 'Av. Central, 222',
        'cidade' => 'Goiânia',
        'estado' => 'GO',
        'cep' => '74040-080',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 11,
        'nome' => 'Camila Ferreira',
        'idade' => 17,
        'sexo' => 'Feminino',
        'email' => 'camila.ferreira@email.com',
        'telefone' => '(11) 99876-5432',
        'endereco' => 'Rua Azul, 333',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'cep' => '04567-091',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
    [
        'id' => 12,
        'nome' => 'Lucas Andrade',
        'idade' => 16,
        'sexo' => 'Masculino',
        'email' => 'lucas.andrade@email.com',
        'telefone' => '(41) 98765-4322',
        'endereco' => 'Rua das Laranjeiras, 444',
        'cidade' => 'Curitiba',
        'estado' => 'PR',
        'cep' => '80010-021',
        'curso' => 'Ensino Médio',
        'ano_letivo' => 2025,
    ],
];

function putExcel(array $data)
{
    $spreadsheet = new Spreadsheet();
    $activeWorksheet = $spreadsheet->getActiveSheet();

    $colums = array_keys($data[0]);
    $col = 'A';

    foreach ($colums as $header) {
        $activeWorksheet->setCellValue("{$col}1", $header);
        $col++;
    }

    $line = 2;
    foreach($data as $register){
        $col = 'A';
        foreach($colums as $colum){
            $activeWorksheet->setCellValue("{$col}$line", $register[$colum]); 
            $col++; 
            var_dump($register[$colum]);
        }
        $line++;
    }
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('teste_2.xlsx');
}

putExcel($alunos);


