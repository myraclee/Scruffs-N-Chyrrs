<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="account_popup" id="accountPopup">
    @auth
        @if(Auth::check() && Auth::user()->isOwner())
            <button type="button" id="viewDashboardButton" class="logout_btn">View Dashboard</button>
        @endif
        <button type="button" id="viewAccountButton" class="logout_btn">View Account</button>
        <button type="button" id="logoutButton" class="logout_btn">Logout</button>
    @endauth

    @guest
    <div class="guest_login">
        <a href="{{ route('login') }}">Login</a>
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#682C7A"><path d="M480-120v-80h280v-560H480v-80h280q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H480Zm-80-160-55-58 102-102H120v-80h327L345-622l55-58 200 200-200 200Z"/></svg>
    </div>
    <div class="guest_signup">
        <a href="{{ route('signup') }}">Sign Up</a>
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#682C7A"><path d="M720-400v-120H600v-80h120v-120h80v120h120v80H800v120h-80ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47ZM40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm80-80h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm296.5-343.5Q440-607 440-640t-23.5-56.5Q393-720 360-720t-56.5 23.5Q280-673 280-640t23.5 56.5Q327-560 360-560t56.5-23.5ZM360-640Zm0 400Z"/></svg>
    </div>
    @endguest
</div>
<!-- Hidden logout form -->
<form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>