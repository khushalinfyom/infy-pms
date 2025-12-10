@extends('layouts.auth_app')
@section('title')
    Reset Password
@endsection
@section('css')
    <link rel="stylesheet" href="{{ asset('web/css/web.css') }}" type="text/css">
@endsection
@section('content')
    <div class="col-md-6 offset-lg-3 offset-md-3 mt-5">
    <div class="card card-primary">
        <div class="card-header"><h4>Reset Password</h4></div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
            <form method="post" action="{{ url('/password/email') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="email">Email:</label> <span class="required">*</span>
                    <input type="email" class="form-control {{ $errors->has('email')?'is-invalid':'' }}" name="email"
                           value="{{ old('email') }}"
                           placeholder="Enter Email" required autofocus tabindex="1">
                    @if ($errors->has('email'))
                        <span class="invalid-feedback">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                    @endif
                </div>

                <div class="form-group">
                    <button class="btn login-btn btn-block  btn-lg" type="submit" tabindex="2">
                        Send Password Reset Link
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
@endsection
