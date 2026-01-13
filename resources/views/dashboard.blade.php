<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="p-6">
        Bạn đang ở Dashboard.
        <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</x-app-layout>
