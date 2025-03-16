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
               
            }

            /** Define now the real margins of every page in the PDF **/
            body {
                margin-top: 4.1cm;
                margin-left: 0cm;
                margin-right: 0cm;
                margin-bottom:6.0cm;
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
                height: 5.9cm;
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
                <img src="{{public_path('img/logo.png')}}"  style="width: 50%; height: 40%;"></img>
            <tr>
                <td>
                <td style="text-align: right">
                    Mérida, Yucatán a de de
            <tr>
                <td colspan="2" style="text-align: center">
                    CARTA DE ENTREGA DE 
            <tr>
                <td style="text-align: left">
                    Confirmo que recibo el , mismo que a continuación se detalla:
                <td>
        </table>

			
        </header>

        <footer>
        <table align="center" border="0" style="width:100%;text-align: center">
                <tr>
                    <td>
                        Entrega:
                    <td>
                        Recibe:
                <tr>
                    <td style="height: 100px;">
                    <td style="height: 100px;">
                <tr>
                    <td>
                        Lic.
                        <br>
                        Aux
                        <br>
                    <td>
                        Julio
            </table>
			
        </footer>

        <!-- Wrap the content of your PDF inside a main tag -->
        <main>

        <table align="center" border="1" style="width:100%">
            <tr>
                <td colspan="2" style="background: black;color: white;">
                    OBRA/UBICACION
                <td colspan="2" style="background: black;color: white;">
                    VIGENCIA DEL COMODATO
            <tr>
                <td colspan="2">
                    NOMBRE OBRA
                <td colspan="2">
                    TERMIACION
            <tr>
                <td colspan="2" style="background: black;color: white;">
                    NUMERO DE CONTACTO
                <td colspan="2" style="background: black;color: white;">
                    GERENCIA
            <tr>
                <td colspan="2">
                    999999999
                <td colspan="2">
                    ADMINISTRATIVA
            <tr>
                <td style="background: black;color: white;">
                    FOLIO
                <td style="background: black;color: white;">
                    NUMERO DE SERIE
                <td colspan="2" style="background: black;color: white;">
                    DESCRIPCION
            <tr>
                <td>
                    XXXX
                <td colspan="2">
                    1111
                <td colspan="2">
                    XXXXX
        </table>

        <br>

        <table align="center" border="0" style="width:100%;text-align: center">       
         <tr>
		    <td style="text-align: left;">
            Este ---- es propiedad de ------ y se me entrega en comodato durante el periodo especificado en la sección superior de vigencia. 
        <tr>
                <td style="text-align: left;padding-left: 50px">
                •	Me comprometo a cuidar, darle buen uso, responder por el equipo y acepto que:  <br>
        <tr>
                <td style="text-align: left;padding-left: 84px;padding-right: 82px;">
                o	En caso de daño, levantar un acta administrativa detallando el hecho y responder por el importe total o parcial del costo de la reparación, según sea el caso.  <br>
                o	En caso de robo, informar de manera inmediata a la empresa e interponer ante la autoridad una denuncia por el hecho y presentar una copia de la misma.  <br>
                o	En caso de extravío, levantar un acta administrativa detallando el hecho y reponer en su totalidad el costo del equipo.  <br>
                o	En caso de reposición del equipo se requiere entregarlo en las mejores condiciones posibles salvo uso cotidiano, en caso de no ser así, me comprometo a responsabilizarme por las reparaciones que se deriven. <br>
                o	Retornar el equipo al departamento de TI al terminar la vigencia del comodato. <br>


                

        </table>


			
        </main>
    </body>
</html>