@extends('core::emails.layout')

@section('title', 'Bienvenue')

@section('header', 'Bienvenue chez nous !')

@section('content')
    <h2>Bonjour {{ $user->name }},</h2>
    <p>Merci de vous être inscrit sur notre plateforme.</p>
    <p>Vous pouvez maintenant accéder à tous nos services.</p>

    <p>
        <a href="{{ $loginUrl }}" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Se connecter
        </a>
    </p>
@endsection
