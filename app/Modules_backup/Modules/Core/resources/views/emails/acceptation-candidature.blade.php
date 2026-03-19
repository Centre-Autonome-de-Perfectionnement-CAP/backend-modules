@extends('core::emails.base')

@section('title', 'Candidature Acceptée')

@section('header')
    <h1 style="color: white;">Félicitations</h1>
    <p style="margin: 5px 0 0 0; color: white;">Votre candidature a été acceptée</p>
@endsection

@section('content')
    <p>Bonjour <strong>{{ $prenoms ?? '' }} {{ $nom ?? 'Candidat(e)' }}</strong>,</p>

    <p>Nous avons le plaisir de vous informer que votre candidature pour la filière <strong>{{ $filiere }}</strong> a été <strong style="color: #4CAF50;">acceptée</strong>.</p>

    <p>Nous vous félicitons pour cette réussite et nous sommes heureux de vous compter parmi les candidats retenus pour cette formation.</p>

    <p>Pour consulter la liste complète des admis ainsi que d'autres informations importantes relatives à votre admission, nous vous invitons à vous rendre sur notre site web à l'adresse suivante : <a href="https://www.cap-epac.online">https://www.cap-epac.online</a>.</p>
    <p>Nous vous remercions de la confiance que vous avez accordée à notre établissement et sommes impatients de vous accueillir. N'hésitez pas à revenir vers nous si vous avez des questions ou besoin d'assistance.</p>

    <p>Cordialement,<br><strong>{{ $etablissement ?? 'Service Informatique du CAP' }}</strong></p>
@endsection
