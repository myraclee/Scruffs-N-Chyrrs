<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="account_popup" id="accountPopup">
    @auth
        @if(Auth::check() && Auth::user()->isOwner())
            <button type="button" id="viewDashboardButton" class="popup_item">
                <span>View Dashboard</span>
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#682C7A"><path d="M120-120v-320h320v320H120Zm0-400v-320h320v320H120Zm400 400v-320h320v320H520Zm0-400v-320h320v320H520Z"/></svg>
            </button>
        @endif
        <button type="button" id="viewAccountButton" class="popup_item">
            <span>View Account</span>
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#682C7A"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-200v-32q0-34 17.5-62.5T224-338q62-31 126-46.5T480-400q66 0 130 15.5T736-338q29 15 46.5 43.5T800-232v32H160Z"/></svg>
        </button>
        <button type="button" id="logoutButton" class="popup_item">
            <span>Logout</span>
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#682C7A"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>
        </button>
    @endauth

    @guest
        <a href="{{ route('login') }}" class="popup_item">
            <span>Login</span>
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#682C7A"><path d="M480-120v-80h280v-560H480v-80h280q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H480Zm-80-160-55-58 102-102H120v-80h327L345-622l55-58 200 200-200 200Z"/></svg>
        </a>
        <a href="{{ route('signup') }}" class="popup_item">
            <span>Sign Up</span>
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#682C7A"><path d="M720-400v-120H600v-80h120v-120h80v120h120v80H800v120h-80ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47ZM40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm80-80h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm296.5-343.5Q440-607 440-640t-23.5-56.5Q393-720 360-720t-56.5 23.5Q280-673 280-640t23.5 56.5Q327-560 360-560t56.5-23.5ZM360-640Zm0 400Z"/></svg>
        </a>
    @endguest
</div>
<form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>