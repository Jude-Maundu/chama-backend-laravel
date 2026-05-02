<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('home') }}">
            <strong>💰 Chama System</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="featuresDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-briefcase"></i> Features
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="featuresDropdown">
                            <li><h6 class="dropdown-header">💰 Financial</h6></li>
                            <li><a class="dropdown-item" href="{{ route('contributions.index') }}">Contributions</a></li>
                            <li><a class="dropdown-item" href="{{ route('loans.index') }}">Loans</a></li>
                            <li><a class="dropdown-item" href="{{ route('goals.index') }}">Goals</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">👥 Community</h6></li>
                            <li><a class="dropdown-item" href="{{ route('members.index') }}">Members</a></li>
                            <li><a class="dropdown-item" href="{{ route('meetings.index') }}">Meetings</a></li>
                            <li><a class="dropdown-item" href="{{ route('events.index') }}">Events</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">📊 Reports</h6></li>
                            <li><a class="dropdown-item" href="{{ route('contributions.report') }}">Contribution Report</a></li>
                            <li><a class="dropdown-item" href="{{ route('reports.financial') }}">Financial Report</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Edit Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">Register</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
