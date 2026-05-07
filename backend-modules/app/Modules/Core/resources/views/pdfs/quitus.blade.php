<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QUITUS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 15px;
            margin: 20px;
        }
        .header, .footer {
            width: 100%;
            text-align: center;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            font-weight: normal;
        }
        th, td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            position: fixed;
            bottom: 8px;
            width: 100%;
        }
        .printed-info {
            position: fixed;
            
            bottom: 0px;
            margin-top:10px;
        }
        .header{
            position: relative;
        }
        .header h1{
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h2{
            font-size: 14px;
            text-transform: uppercase;
        }
        .header h3{
            font-size: 13px;
        }
        .logo-header{
            position: absolute;
            right: 0;
             height: 100px;
        }
        .logo-header.epac{
            left: 0;
            text-align: left;
        }
        .header p{
            font-weight: normal;
            font-size: 10px;
        }
         .section {
            margin-bottom: 10px;
        }
         .titre {
             
            font-size: 30px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
             margin-bottom: 20px;
        }
        .info-table,.info-table tr,.info-table td{
            border: none;
        }
        .info-table td:first-child{
            width: 70%;
        }
        .info-table td{
            overflow: hidden;
        }
        .no-border,.no-border tr,.no-border td{
            border: none;
            margin-top: 15px;
            margin-bottom: 70px;
        }
        
    </style>
</head>
<body>
    <div class="header">

        <img src='{{ public_path("assets/epac.png") }}' alt="logo-epac" class="logo-header epac">
        <img src='{{ public_path("assets/cap.png") }}' alt="logo-cap"  class="logo-header">
        <h3 style="margin:0px">Université d'Abomey-Calavi</h3>

        <img src='{{ public_path("assets/banner.png") }}' alt="header-separator-img" style="margin:0px ;">
        <h2 style="margin:0">Ecole Polytechnique d'Abomey-Calavi</h2>
        <img src='{{ storage_path("assets/banner.png") }}' alt="header-separator-img" style="margin:0px;">
        <h1 style="margin:0;">Centre Autonome de Perfectionnement</h1>
        <p>
            01 BP 2009 COTONOU - TEl. 21 36 14 32/21 36 09 93 - Email. epac.uac@epac.uac.bj
        </p>
        <hr>
        <br/>
        <br/>
        <br/>
        <br/>
        <br/>
         <div class="titre">
              QUITUS
         </div>
      <?php
            $date = date('d/m/Y');
      ?>
        <!--<h2>FICHE RECAPITULATIF DE NOTES</h4>-->
    </div>
    
    <div class="section">
            <div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify; ">Je soussigné <strong>{{ $titre }} {{ $nom }} {{ $prenom }}</strong>, superviseur du mémoire de l'étudiant <strong>{{ $nometu }} {{ $prenometu }}</strong>, l'autorise à déposer son {{ $diplome == 'Licence Professionnelle' ? 'rapport' : 'mémoire' }} de fin de {{$diplome}} en {{$filiere}} portant sur le thème : «  <strong>{{ $intitule }}</strong> », en vue de sa soutenance.</div>
            <br/>
            <div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify; ">En foi de quoi, le présent quitus lui est délivré pour servir et valoir ce que de droit.</div>
            
             <br/>
            <div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify ;text-align: right; ">Fait à Abomey-Calavi, le <?= date('d/m/Y'); ?>.</div>

             <br/>
            <div style="font-weight: bold; text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify ;text-align: right; ">  Superviseur de Mémoire</div>
            <br/>
            
             <br/>
             
             <br/>
             
             <br/>
             
             <br/>
            <div style="font-weight: bold; text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify ;text-align: right; ">  {{ $titre }} {{ $nom }} {{ $prenom }}</div>
<div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify ;text-align: right; ">{{ $grade }}</div>
<div style="text-indent: 2em; font-size: 18px; line-height: 1.8; text-align: justify ;text-align: right; ">Enseignant Chercheur à l'EPAC/UAC</div>
            
        </div>
    <hr class="footer">
    <div class="printed-info" style="font-size: 8px; font-weight: bold; " >
       <em style="text-align: center;">{{ $intitule }} </em>
    </div>
</body>
</html>
