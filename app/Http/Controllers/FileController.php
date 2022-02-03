<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use App\Models\File;

class FileController extends Controller
{
    public function index() { 
        $path = public_path('/assets/upload');

        $files = \File::allFiles($path);

        foreach ($files as $key => $file) {
            $extension = Str::lower(\File::extension($file));

            if($extension == "pdf"){
                $pdfParser = new Parser();
                $pdf = $pdfParser->parseFile($file);
                $content = $pdf->getText();

                $parse_result = array();
                if(Str::contains($content, "www.chubb.com/mx")){
                    $parse_result = $this->get_chubb_content_from_pdf($content);

                    echo $file."<br/>";
                    print_r($parse_result);
                }else if(Str::contains($content, "www.qualitas.com.mx") && Str::contains($content, "Quálitas Compañia de Seguros, S.A. de C.V.")){
                    $parse_result = $this->get_qualitas_content_from_pdf($content);

                    echo $file."<br/>";
                    print_r($parse_result);
                }else{
                    echo $file."<br/>";
                    echo "An error of no match found<br/>";
                }
            }else{
                echo $file."<br/>";
                echo "An error of no match found<br/>";
            }
        }
    }

    public function get_chubb_content_from_pdf($content){
        $match = Str::of($content)->match('/Póliza:(.*)Vigencia:(.*)/i');

        $result['Poliza'] = trim($match);

        $match = Str::of($content)->match('/Contratante:(.*)/i');
        
        $result['Nombre'] = trim($match);

        $match = Str::of($content)->match('/Vigencia:(.*)/i');
        preg_match("/del(.*)al(.*)/i", $match, $matches);

        $result['Vigencia del'] = isset($matches[1]) ? trim($matches[1]) : "";
        $result['Vigencia al'] = isset($matches[2]) ? trim($matches[2]) : "";

        $match = Str::of($content)->match('/Asegurado:(.*)C.P.(.*)/i');
        
        $result['Asegurado'] = trim($match);

        $match = Str::of($content)->match('/Contratante:(.*)/i');
        
        $result['Contratante'] = trim($match);

        $match = Str::of($content)->match('/Domicilio:(.*)Teléfono:(.*)/i');
        $match1 = Str::of($content)->match('/(.*)RFC:(.*)/i');
        
        $result['Domicilio'] = trim($match).", ".trim($match1);

        $match = Str::of($content)->match('/C.P.:(.*)/i');
        
        $result['CP'] = trim($match);

        $match = Str::of($content)->match('/Teléfono:(.*)/i');
        
        $result['Telefono'] = trim($match);

        $match = Str::of($content)->match('/RFC:(.*)/i');
        
        $result['RFC'] = trim($match);

        $match = Str::of($content)->match('/Paquete:(.*)/i');
        
        $result['Paquete'] = trim($match);

        $match = Str::of($content)->match('/Moneda:(.*)Forma(.*)/i');
        
        $result['Moneda'] = trim($match);

        $match = Str::of($content)->match('/Forma de Pago:(.*)/i');
        
        $result['Forma de Pago'] = trim($match);

        $match = Str::of($content)->match('/Fecha de Emisión:(.*)Descuento(.*)/i');
        
        $result['Fecha de Emisión'] = trim($match);

        $match = Str::of($content)->match('/Prima Neta(.*)/i');
        $split = Str::of(trim($match))->split('/[\s]+/');

        $result['Prima Neta'] = floatval(preg_replace("/[^-0-9\.]/", "", $split[sizeof($split) - 1]));

        $match = Str::of($content)->match('/Otros descuentos(.*)/i');
        $split = Str::of(trim($match))->split('/[\s]+/');

        $result['Otros descuentos'] = floatval(preg_replace("/[^-0-9\.]/", "", $split[sizeof($split) - 1]));

        $match = Str::of($content)->match('/Financiamiento por pago fraccionado(.*)/i');
        $split = Str::of(trim($match))->split('/[\s]+/');

        $result['Financiamiento por pago fraccionado'] = floatval(preg_replace("/[^-0-9\.]/", "", $split[sizeof($split) - 1]));

        $match = Str::of($content)->match('/Gastos de expedición(.*)/i');
        $split = Str::of(trim($match))->split('/[\s]+/');

        $result['Gastos de expedición'] = floatval(preg_replace("/[^-0-9\.]/", "", $split[sizeof($split) - 1]));

        $match = Str::of($content)->match('/I.V.A.(.*)/i');
        $split = Str::of(trim($match))->split('/[\s]+/');

        $result['I.V.A.'] = floatval(preg_replace("/[^-0-9\.]/", "", $split[sizeof($split) - 1]));

        $match = Str::of($content)->match('/Prima Total(.*)/i');
        $split = Str::of(trim($match))->split('/[\s]+/');

        $result['Prima Total'] = floatval(preg_replace("/[^-0-9\.]/", "", $split[sizeof($split) - 1]));

        return $result;
    }

    public function get_qualitas_content_from_pdf($content){
        $content_arr = Str::of($content)->split('/(\r\n|\n|\r)/');

        $match = Str::of($content)->match('/([0-9]{10})/');

        $result['Poliza'] = trim($match);

        $result['Contratante'] = isset($content_arr[5]) ? trim($content_arr[5]) : "";

        $result['Producto'] = isset($content_arr[6]) ? trim($content_arr[6]) : "";

        $match = Str::of($content)->match('/Plan:(.*)/i');
        
        $result['Plan'] = trim($match);

        $result['Domicilio'] = isset($content_arr[65]) ? trim($content_arr[65]) : "";

        $split = isset($content_arr[66]) ? Str::of(trim($content_arr[66]))->split('/[\s]+/') : "";

        $result['C.P.'] = isset($split[0]) ? trim($split[0]) : "";
        
        $result['Municipio'] = isset($split[1]) ? trim($split[1]) : "";
        
        $result['Estado'] = isset($split[2]) ? trim($split[2]) : "";
        
        $result['Colonia'] = isset($split[3]) ? trim($split[3]) : "";
        $result['Colonia'] = isset($split[4]) ? $result['Colonia']." ".trim($split[4]) : $result['Colonia'];

        $result['R.F.C.'] = isset($content_arr[67]) ? trim($content_arr[67]) : "";

        $result['Vehiculo'] = isset($content_arr[5]) ? trim($content_arr[5]) : "";

        $match = Str::of($content)->match('/Desde las(.*)del:(.*)Hasta las(.*)/i');
        $split = isset($content_arr[7]) ? Str::of(trim($content_arr[7]))->split('/[\s]+/') : "";

        $result['Vigencia Desde'] = isset($split[0]) ? trim($match).trim($split[0]) : trim($match);
        
        $match = Str::of($content)->match('/Hasta las(.*)del:(.*)/i');

        $result['Vigencia Hasta'] = isset($split[1]) ? trim($match).trim($split[1]) : trim($match);

        $split = isset($content_arr[86]) ? Str::of(trim($content_arr[86]))->split('/[\s]+/') : "";

        $result['Plazo de Pago'] = isset($split[0]) ? trim($split[0]) : "";

        $result['Fecha Vencimiento del pago'] = isset($content_arr[85]) ? trim($content_arr[85]) : "";

        $result['Uso'] = isset($content_arr[87]) ? trim($content_arr[87]) : "";

        $split = isset($content_arr[104]) ? Str::of(trim($content_arr[104]))->split('/[\s]+/') : "";

        $result['Prima Neta'] = floatval(preg_replace("/[^-0-9\.]/", "", $split[0]));

        $split = Str::of(trim($split[1]))->split('/\./');
        $num1 = $split[0].".".Str::of($split[1])->substr(0, 2);
        $num2 = Str::of($split[1])->substr(2).".".$split[2];
        
        $result['Financiamiento'] = floatval(preg_replace("/[^-0-9\.]/", "", $num1));

        $result['Expedición'] = floatval(preg_replace("/[^-0-9\.]/", "", $num2));

        $result['I.V.A.'] = floatval(preg_replace("/[^-0-9\.]/", "", $content_arr[106]));

        $result['IMPORTE TOTAL'] = floatval(preg_replace("/[^-0-9\.]/", "", $content_arr[107]));

        $match = Str::of($content)->match('/Forma de:Pago:(.*)/i');
        $split = Str::of(trim($match))->split('/[\s]+/');

        $result['Forma de Pago'] = trim($split[0]);

        return $result;
    }
}
