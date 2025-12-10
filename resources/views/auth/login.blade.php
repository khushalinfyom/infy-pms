@extends('layouts.auth_app')
@section('title')
    Login
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('web/css/web.css') }}" type="text/css">
@endsection
@section('content')
    <div class="row login-container">
        <div class="col-md-6 img-box">
            <img src="{{asset('assets/img/login-banner.png')}}" width="100%">
        </div>
        <div class="col-md-5">
            <div class="login-brand mb-0">
                <img src="{{ !empty(getLogoUrl()) ? getLogoUrl() : asset('assets/img/logo-red-black.png') }}" alt="logo"
                     width="100">
            </div>
            <div class="card p-0">
                <div class="login-box mt-0">
                    @if( Session::has( 'error' ))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <li>{{ Session::get( 'error' ) }}</li>
                            </ul>
                        </div>

                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger p-0 row">
                            <ul class="mt-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form method="post" action="{{ url('/login') }}">
                        {{ csrf_field() }}
                        <div class="login-row row">
                            <h5 class="text-primary">Login</h5>
                        </div>
                        <div class="login-row row no-margin">
                            <i class="fas fa-envelope"></i>
                            <input type="email"
                                   class="ml-3 form-control form-control-md {{ $errors->has('email')?'is-invalid':'' }}"
                                   name="email"
                                   value="{{ (Cookie::get('email') !== null) ? Cookie::get('email') : old('email') }}"
                                   placeholder="Enter Email" autofocus tabindex="1">
                            <div class="invalid-feedback">
                                {{ $errors->first('email') }}
                            </div>
                        </div>
                        <div class="login-row row no-margin">
                            <i class="fas fa-unlock-alt"></i>
                            <input type="password"
                                   class="ml-3 form-control form-control-md {{ $errors->has('password')?'is-invalid':'' }}"
                                   value="{{ (Cookie::get('password') !== null) ? Cookie::get('password') : null }}"
                                   name="password"
                                   placeholder="Enter Password" tabindex="2">
                            <div class="invalid-feedback">
                                {{ $errors->first('password') }}
                            </div>
                        </div>
                        <div class="login-row row">
                            <p class="ml-4"><input type="checkbox" name="remember" tabindex="3"
                                                   id="remember"{{ (Cookie::get('remember') !== null) ? 'checked' : '' }}>
                                &nbsp;Remember Me</p>
                            <p class="ml-auto"><a href="{{route('password.request')}}" class="text-small" tabindex="4">
                                    Forgot Password?
                                </a>
                            </p>
                        </div>
                        @if(!empty($setting['show_recaptcha']))
                            @php
                                  $sitekey  = $setting['google_recaptcha_site_key'];
                                  $secret = $setting['google_recaptcha_secret_key'];
                                   $captcha = new \Anhskohbo\NoCaptcha\NoCaptcha($secret, $sitekey);
                            
                            @endphp
                            <?php echo $captcha->display(); ?>
                            <?php echo $captcha->renderJs(); ?>
                        @endif
                        <br>
                        <div class="ml-1 row">
                            <button type="submit" class="btn login-btn btn-lg btn-block" tabindex="5">
                                Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
