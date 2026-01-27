@if ($paginator->hasPages())
    <div class="row mt-5 justify-content-center">
    <div class="col-md-6 col-12" style = "display:contents;">
    <nav aria-label="Page navigation example">
        <ul class = "pagination">
            <!-- Previous Page Link -->
            <li class="page-item {{ !$paginator->previousPageUrl() ? 'disabled' : '' }}">
                <a href="{{ $paginator->previousPageUrl() }}" class="page-link next"> <span aria-hidden="true"> <i class="fa fa-angle-left" aria-hidden="true"></i> </span> </a>
            </li>

            <!-- Pagination Elements -->
            @foreach ($elements as $element)
                <!-- "Three Dots" Separator -->
                @if (is_string($element))
                    <li class="page-item">
                        <a href="#">{{ $element }}</a>
                    </li>
                @endif

                <!-- Array Of Links -->
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active">
                                <a class="page-link active" href="#">{{ $page }}</a>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <!-- Next Page Link -->
            <li class="page-item {{ !$paginator->hasMorePages() ? 'disabled' : '' }}">
                <a href="{{ $paginator->nextPageUrl() }}" class="page-link next"><span aria-hidden="true"> <i class="fa fa-angle-right" aria-hidden="true"></i></span> </a>
            </li>
        </ul>
    </nav>
    </div>
    </div>
@endif
