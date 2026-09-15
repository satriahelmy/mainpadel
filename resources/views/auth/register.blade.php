@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-md py-10 sm:py-16">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Start your account</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">Create account</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Save your games and pick up where you left off.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <x-ui.input label="Name" id="name" name="name" autocomplete="name" required autofocus />
            </div>

            <div>
                <x-ui.input label="Email" id="email" name="email" type="email" autocomplete="email" required />
            </div>

            <div>
                <x-ui.input label="Password" id="password" name="password" type="password" autocomplete="new-password" required />
            </div>

            <div>
                <x-ui.input label="Confirm password" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required />
            </div>

            <x-ui.button type="submit" class="min-h-13 w-full text-base">Create account</x-ui.button>
        </form>

        <p class="mt-6 text-center text-sm text-stone-600">Already have an account? <a href="{{ route('login') }}" class="font-bold text-stone-950 underline underline-offset-4">Sign in</a></p>
    </section>
@endsection
