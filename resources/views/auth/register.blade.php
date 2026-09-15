@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-md py-10 sm:py-16">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Start your account</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950">Create account</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Save your games and pick up where you left off.</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-stone-800">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-stone-800">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-stone-800">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-stone-800">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 bg-white px-4 text-base text-stone-950 placeholder:text-stone-400">
            </div>

            <button type="submit" class="min-h-13 w-full rounded-xl bg-[#c7f000] px-5 text-base font-bold text-stone-950 transition hover:bg-[#b8df00]">Create account</button>
        </form>

        <p class="mt-6 text-center text-sm text-stone-600">Already have an account? <a href="{{ route('login') }}" class="font-bold text-stone-950 underline underline-offset-4">Sign in</a></p>
    </section>
@endsection
