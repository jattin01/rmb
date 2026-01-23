@extends('layouts.auth.app')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="px-sm-4">
            <div class="row mt-0 mt-sm-3 align-items-center justify-content-between">
                <div class="col-md-4 mb-sm-0 mb-2">
                    <div class="top-head">
                        <h1>Settings</h1>
                        <p>Home</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class = "row">
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.batchingPlants.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/batching_plants_icon.svg')}}" />
                                </div>
                                <div>
                                    Batching Plant
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.transitMixers.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/transit_mixer_icon.svg')}}" />
                                </div>
                                <div>
                                    Transit Mixer
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.pumps.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/pumps_icon.svg')}}" />
                                </div>
                                <div>
                                    Pump
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.companyLocations.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/locations_icon.svg')}}" />
                                </div>
                                <div>
                                    Location
                                </div>
                            </div>
                        </div>

                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.products.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/mix_icon.svg')}}" />
                                </div>
                                <div>
                                    Mix
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.productTypes.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/mix_types_icon.svg')}}" />
                                </div>
                                <div>
                                    Mix Type
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.users.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/users_icon.svg')}}" />
                                </div>
                                <div>
                                    User
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.drivers.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/customers_icon.svg')}}" />
                                </div>
                                <div>
                                    Driver
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.orderApproval.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/customers_icon.svg')}}" />
                                </div>
                                <div>
                                    Approval Workflow
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.structure.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/customers_icon.svg')}}" />
                                </div>
                                <div>
                                   Structure
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.global.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/customers_icon.svg')}}" />
                                </div>
                                <div>
                                   Global
                                </div>
                            </div>
                        </div>
                        <div class = "col-md-3 text-center">
                            <div class = "settings-block" data-url = "{{route('settings.capacity.index')}}" onclick = "redirectToPage(this)">
                                <div class = "settings-block-img">
                                <img src = "{{asset('assets/img/customers_icon.svg')}}" />
                                </div>
                                <div>
                                   Capacity
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function redirectToPage(element)
    {
        window.location.href = element.dataset.url;
    }
</script>
@endsection
