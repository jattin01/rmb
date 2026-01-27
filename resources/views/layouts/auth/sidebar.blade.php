<aside class="main-sidebar sidebar-dark-primary">

			<div class="text-right leftmenuaction navbg">
				<a class=" menucloico closemenu-big" data-widget="pushmenu" href="#" role="button">
					<i class="fa fa-arrow-left"></i>
				</a>
				<a href="#" class="menuexpico closemenu-bigopen" data-widget="pushmenu" role="button">
					<img src="{{asset('assets/img/menu-left-alt.svg')}}" width="25px">
				</a>
			</div>

			<div class="sidebar">

				<nav class="mt-5">
					<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
						data-accordion="false">

						<li class="nav-item"><a href="{{route('dashboard.index')}}" class="nav-link {{ Request::is('home') ? 'active' : '' }}">
								<img src="{{asset('assets/img/home.svg')}}" alt="">
								<div class = "sidebar-label">Home</div>
							</a></li>
						<li class="nav-item"><a href="{{route('web.order.live.schedule')}}" class="nav-link {{ Request::is('live-schedule') ? 'active' : '' }}">
								<img src="{{asset('assets/img/order.svg')}}" alt="">
								<div class = "sidebar-label">Schedule</div>
							</a></li>
						<li class="nav-item"><a href="{{route('orders.overview')}}" class="nav-link {{ Request::is('orders-overview') ? 'active' : '' }}">
								<img src="{{asset('assets/img/overview.svg')}}" alt="">
								<div class = "sidebar-label">Orders</div>

							</a></li>
						<li class="nav-item"><a href="{{route('customers.index')}}" class="nav-link {{ Request::is('customers*') ? 'active' : '' }}">
								<img src="{{asset('assets/img/left-menu5.svg')}}" alt="">
								<div class = "sidebar-label">Customers</div>

							</a></li>
						<li class="nav-item"><a href="{{route('settings.customerProjects.index')}}" class="nav-link {{ Request::is('customer-projects*') ? 'active' : '' }}">
								<img src="{{asset('assets/img/projects_icon.svg')}}" alt="">
								<div class = "sidebar-label">Projects</div>

							</a></li>
                            <li class="nav-item"><a href="{{route('resources.dashboard')}}" class="nav-link {{ Request::is('resources.dashboard') ? 'active' : '' }}">
								<img src="{{asset('assets/img/plant.svg')}}" alt="">
								<div class = "sidebar-label">Reports</div>
							</a></li>
						<li class="nav-item"><a href="{{route('settings.home')}}" class="nav-link {{ Request::is('*settings*') ? 'active' : '' }}">
								<img src="{{asset('assets/img/left-menu6.svg')}}" alt="">
								<div class = "sidebar-label">Settings</div>
							</a>
						</li>


					</ul>
				</nav>
			</div>
		</aside>
