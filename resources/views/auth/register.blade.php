@extends('layouts.auth_app')
@section('title')
    Register
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('web/css/web.css') }}" type="text/css">
@endsection
@section('content')
    <div class="card card-primary">
        <div class="card-header"><h4>Register</h4></div>
        <div class="card-body pt-1">
            <form method="post" action="{{ url('/register') }}">
                {{ csrf_field() }}
                @if ($errors->any())
                    <div class="alert alert-danger p-0">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="row">
                    <div class="col-sm-6 col-lg-6 col-md-12">
                        <div class="form-group">
                            <label for="first_name">Full Name</label><span class="text-danger">*</span>
                            <input type="text"
                                   class="form-control{{ $errors->has('name')?'is-invalid':'' }}"
                                   name="name" value="{{ old('name') }}"
                                   placeholder="Enter Full Name" autofocus tabindex="1">
                            <div class="invalid-feedback">
                                {{ $errors->first('name') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6 col-md-12">
                        <div class="form-group">
                            <label for="email">Email</label><span class="text-danger">*</span>
                            <input type="email"
                                   class="form-control {{ $errors->has('email')?'is-invalid':'' }}"
                                   name="email" value="{{ old('email') }}" placeholder="Enter Email" tabindex="2">
                            <div class="invalid-feedback">
                                {{ $errors->first('email') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6 col-md-12">
                        <div class="form-group">
                            <label for="password" class="control-label">Password</label><span
                                    class="text-danger">*</span>
                            <input type="password"
                                   class="form-control {{ $errors->has('password')?'is-invalid':''}}"
                                   name="password" placeholder="Enter Password" tabindex="3">
                            <div class="invalid-feedback">
                                {{ $errors->first('password') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-6 col-md-12">
                        <div class="form-group">
                            <label for="password_confirmation" class="control-label">Confirm
                                Password</label><span
                                    class="text-danger">*</span>
                            <input type="password" name="password_confirmation" class="form-control"
                                   placeholder="Enter Confirm password" tabindex="4">
                            <div class="invalid-feedback">
                                {{ $errors->first('password_confirmation') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mt-4">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="5">
                                Register
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="mt-5 text-muted text-center">
        Already have an account? <a href="{{ route('login') }}">Sign In</a>
    </div>
@endsection
