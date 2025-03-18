<html>
    <head>
        <style>
            /** 
                Set the margins of the page to 0, so the footer and the header
                can be of the full height and width !
             **/
            @page {
                margin: 0.9cm 0.9cm;
                font-family: Arial,Georgia, serif;
                width: 215.9mm;    /* Ancho carta */
                height: 279.4mm;   /* Alto carta */
                font-size:14px;
               
            }

            /** Define now the real margins of every page in the PDF **/
            body {
                margin-top: 4.1cm;
                margin-left: 0cm;
                margin-right: 0cm;
                margin-bottom:7.5cm;
			/* background: #2ffd39;*/
            }

            /** Define the header rules **/
            header {
                position: fixed;
                top: 0cm;
                left: 0cm;
                right: 0cm;
                height: 4.0cm;
				/*background: #fd2f2f;*/
            }

            /** Define the footer rules **/
            footer {
                position: fixed; 
                bottom: 0cm; 
                left: 0cm; 
                right: 0cm;
                height: 7.3cm;
				/*background: #2f70fd;*/
            }

			


        </style>
    </head>
    <body>
       
        <header>
        <table align="center" border="0" style="width:100%" >
            <tr>
                <td>
                <td style="text-align: right">
                <img src="{{public_path('img/logo.png')}}"  style="width: 60%; height: 40%;"></img>
            <tr>
                <td>
                <td style="text-align: right">
                    Mérida, Yucatán a {{$fecha}}
            <tr>
                <td colspan="2" style="text-align: center">
                    CARTA DE ENTREGA DE 
                    @if ($TipoFor == "1" or $TipoFor == "2" or $TipoFor == "4")
                        EQUIPO TI
                    @else
                        TELEFONIA
                    @endif

            <tr>
                <td style="text-align: left">
                    Confirmo que recibo el , mismo que a continuación se detalla:
                <td>
        </table>

			
        </header>

        <footer>
        <table align="center" border="0" style="width:100%; text-align: center">
            <tr>
                <td style="width: 50%">Entrega:</td>
                <td style="width: 50%">Recibe:</td>
            </tr>
            <tr>
                <td style="height: 100px"></td>
                <td style="height: 100px"></td>
            </tr>
            <tr>
                <td >
                    {{$entrega}}<br>
                    {{$entregapuesto}}<br>
                </td>
                <td >
                    {{$recibe}}<br>
                    {{$recibepuesto}}<br>
                </td>
            </tr>
        </table>

			
        </footer>

        <!-- Wrap the content of your PDF inside a main tag -->
        <main>

        
        @if ($TipoFor == 4)
            <!-- Tabla especial para TipoFor == 4 (Equipos + Insumos con solo Número de Serie y Descripción) -->
            <table align="center" border="1" style="text-align: center;width:100%; border: 1px solid black; border-collapse: collapse;">
                <tr>
                    <td style="background: black; color: white; border: 1px solid black;">
                        NÚMERO DE SERIE
                    </td>
                    <td style="background: black; color: white; border: 1px solid black;">
                        DESCRIPCIÓN
                    </td>
                </tr>
                <!-- Equipos -->
                @foreach ($equipos as $equipo)
                    <tr>
                        <td style="border: 1px solid black;">{{ $equipo->NumSerie ?? 'N/A' }}</td>
                        <td style="border: 1px solid black;">{{ $equipo->Caracteristicas ?? 'Sin descripción' }}</td>
                    </tr>
                @endforeach
                <!-- Insumos -->
                @if (!empty($insumos) && count($insumos) > 0)
                    @foreach ($insumos as $insumo)
                        <tr>
                            <td style="border: 1px solid black;">{{ $insumo->NumSerie ?? 'N/A' }}</td>
                            <td style="border: 1px solid black;">{{ $insumo->Comentarios ?? 'Sin descripción' }}</td>
                        </tr>
                    @endforeach
                @endif
            </table>
        @else
            <!-- Tabla normal para otros tipos -->
            <table align="center" border="1" style="text-align: center;width:100%; border: 1px solid black; border-collapse: collapse;">
                <tr>
                    <td colspan="2" style="background: black; color: white; border: 1px solid black;">
                        OBRA/UBICACION
                    </td>
                    <td colspan="2" style="background: black; color: white; border: 1px solid black;">
                        VIGENCIA DEL COMODATO
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border: 1px solid black;">
                        {{$obra}}
                    </td>
                    <td colspan="2" style="border: 1px solid black;">
                        {{$acomodato}}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="background: black; color: white; border: 1px solid black;">
                        NÚMERO DE CONTACTO
                    </td>
                    <td colspan="2" style="background: black; color: white; border: 1px solid black;">
                        GERENCIA
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border: 1px solid black;">
                        {{$telefono}}
                    </td>
                    <td colspan="2" style="border: 1px solid black;">
                        {{$gerencia}}
                    </td>
                </tr>
                <tr>
                    <td style="background: black; color: white; border: 1px solid black;">
                        @if ($TipoFor == 3)
                            NÚMERO DE TELÉFONO
                        @else
                            FOLIO
                        @endif
                    </td>
                    <td style="background: black; color: white; border: 1px solid black;">
                        NÚMERO DE SERIE
                    </td>
                    <td colspan="2" style="background: black; color: white; border: 1px solid black;">
                        DESCRIPCIÓN
                    </td>
                </tr>
                @foreach ($equipos as $equipo)
                    <tr>
                        @if ($TipoFor == 3)
                            <td style="border: 1px solid black;">{{ $equipo->NumTelefonico }}</td>
                        @else
                            <td style="border: 1px solid black;">{{ $equipo->Folio }}</td>
                        @endif
                        <td style="border: 1px solid black;">{{ $equipo->NumSerie ?? 'N/A' }}</td>
                        <td colspan="2" style="border: 1px solid black;">{{ $equipo->Caracteristicas ?? 'Sin descripción' }}</td>
                    </tr>
                @endforeach
                @if (!empty($insumos) && count($insumos) > 0)
                    @foreach ($insumos as $insumo)
                        <tr>
                            <td style="border: 1px solid black;">{{ $insumo->folio ?? 'N/A' }}</td>
                            <td style="border: 1px solid black;">{{ $insumo->NumSerie ?? 'N/A' }}</td>
                            <td colspan="2" style="border: 1px solid black;">{{ $insumo->Comentarios ?? 'Sin descripción' }}</td>
                        </tr>
                    @endforeach
                @endif
            </table>
        @endif


        <br>

        @if ($TipoFor == "4")
        <table align="center" border="0" style="width:100%;text-align: center">       
         <tr>
		    <td style="text-align: center;">
                Por favor de firmar este documento para confirmar lo recibido.
            </table>  

        @else   
        <table align="center" border="0" style="width:100%;text-align: center">       
         <tr>
		    <td style="text-align: left;">
            El equipo o los equipos es propiedad de {{$empresa}} y se me entrega en comodato durante el periodo especificado en la sección superior de vigencia. 
        <tr>
                <td style="text-align: left;padding-left: 50px">
                •	Me comprometo a cuidar, darle buen uso, responder por el equipo y acepto que:  <br>
        <tr>
                <td style="text-align: left;padding-left: 84px;padding-right: 82px;">
                o	En caso de daño, levantar un acta administrativa detallando el hecho y responder por el importe total o parcial del costo de la reparación, según sea el caso.  <br>
                o	En caso de robo, informar de manera inmediata a la empresa e interponer ante la autoridad una denuncia por el hecho y presentar una copia de la misma.  <br>
                o	En caso de extravío, levantar un acta administrativa detallando el hecho y reponer en su totalidad el costo del equipo.  <br>
                o	En caso de reposición del equipo se requiere entregarlo en las mejores condiciones posibles salvo uso cotidiano, en caso de no ser así, me comprometo a responsabilizarme por las reparaciones que se deriven. <br>
                o	Retornar el equipo al departamento de TI al terminar la vigencia del comodato 
                    @if ($TipoFor == "2")
                        ,incluido los accesorios pertinentes (cable de alimentación, cargador y antena).
                    @elseif ($TipoFor == "3")
                    ,incluido los accesorios pertinentes (SIM).
                    @else
                       .
                    @endif <br>
        </table>

        @endif

			
        </main>
    </body>
</html>