@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-md py-10 sm:py-16">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Welcome back</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">Sign in</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Keep your games and player rotations in one place.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <x-ui.input label="Email" id="email" name="email" type="email" autocomplete="email" required autofocus />
            </div>

            <div>
                <x-ui.input label="Password" id="password" name="password" type="password" autocomplete="current-password" required />
            </div>

            <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700">
                <input type="checkbox" name="remember" value="1" class="h-5 w-5 accent-lime-500">
                Remember me
            </label>

            <x-ui.button type="submit" class="min-h-13 w-full text-base">Sign in</x-ui.button>
        </form>

        <p class="mt-6 text-center text-sm text-stone-600">New to MainPadel? <a href="{{ route('register') }}" class="font-bold text-stone-950 underline underline-offset-4">Create an account</a></p>
    </section>
@endsection
