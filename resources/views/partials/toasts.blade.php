@php
    $toastSuccess = session('success') ?: session('status');
@endphp

@if($toastSuccess || $errors->any())
    <div class="app-toast-region" data-toast-region aria-live="polite" aria-atomic="true">
        @if($toastSuccess)
            <section class="app-toast is-success" data-toast data-toast-duration="4000" role="status">
                <span class="app-toast-icon" aria-hidden="true">✓</span>
                <div class="app-toast-content">
                    <strong>操作成功</strong>
                    <p>{{ $toastSuccess }}</p>
                </div>
                <button type="button" data-toast-close aria-label="关闭通知">×</button>
            </section>
        @endif

        @if($errors->any())
            <section class="app-toast is-error" data-toast data-toast-duration="6000" role="alert">
                <span class="app-toast-icon" aria-hidden="true">!</span>
                <div class="app-toast-content">
                    <strong>请检查输入</strong>
                    @if($errors->count() === 1)
                        <p>{{ $errors->first() }}</p>
                    @else
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <button type="button" data-toast-close aria-label="关闭通知">×</button>
            </section>
        @endif
    </div>
@endif
