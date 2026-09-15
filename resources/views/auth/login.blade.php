@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-md py-10 sm:py-16">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Welcome back</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">Sign in</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Keep your games and player rotations in one place.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-stone-800">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-stone-800">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400">
            </div>

            <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700">
                <input type="checkbox" name="remember" value="1" class="h-5 w-5 accent-lime-500">
                Remember me
            </label>

            <button type="submit" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 transition hover:bg-[#b8df00]">Sign in</button>
        </form>

        <p class="mt-6 text-center text-sm text-stone-600">New to MainPadel? <a href="{{ route('register') }}" class="font-bold text-stone-950 underline underline-offset-4">Create an account</a></p>
    </section>
@endsection
