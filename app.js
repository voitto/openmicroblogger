// Generated by CoffeeScript 1.6.3
(function() {
  var Home, HomeController, Person, Post, Show, app, _ref, _ref1, _ref2, _ref3, _ref4,
    __hasProp = {}.hasOwnProperty,
    __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; };

  app = require('zygote').config({
    port: 4444,
    dbname: 'omb2',
    dbuser: 'brian',
    dbpass: '',
    dbhost: 'localhost',
    dbport: 5432,
    url: 'http://localhost:4444'
  });

  Post = (function(_super) {
    __extends(Post, _super);

    function Post() {
      _ref = Post.__super__.constructor.apply(this, arguments);
      return _ref;
    }

    Post.prototype.save = function(f) {
      return $.ajax({
        url: '/post/save',
        complete: f,
        data: JSON.stringify({
          title: $('#post-title').val(),
          author: $('#post-author').val(),
          author_url: $('#post-author_url').val(),
          in_reply_to: $('#post-in_reply_to').val()
        })
      });
    };

    return Post;

  })(app.Model);

  Person = (function(_super) {
    __extends(Person, _super);

    function Person() {
      _ref1 = Person.__super__.constructor.apply(this, arguments);
      return _ref1;
    }

    Person.prototype.signin = function(f) {
      return $.ajax({
        url: '/person/signin',
        complete: f,
        data: JSON.stringify({
          email: $('#user-email').val(),
          password: $('#user-password').val()
        })
      });
    };

    Person.prototype.signup = function(f) {
      return $.ajax({
        url: '/person/signup',
        complete: f,
        data: JSON.stringify({
          email: $('#signup-email').val(),
          password: $('#signup-password').val()
        })
      });
    };

    Person.prototype.signout = function(f) {
      return $.ajax({
        url: '/person/signout',
        complete: f,
        data: JSON.stringify({})
      });
    };

    return Person;

  })(app.Model);

  Home = (function(_super) {
    __extends(Home, _super);

    function Home() {
      _ref2 = Home.__super__.constructor.apply(this, arguments);
      return _ref2;
    }

    Home.prototype.init = function(model, req, res, id, Person) {
      return this.controller = new HomeController(this.model, this, null, Person);
    };

    return Home;

  })(app.View);

  Show = (function(_super) {
    __extends(Show, _super);

    function Show() {
      _ref3 = Show.__super__.constructor.apply(this, arguments);
      return _ref3;
    }

    Show.prototype.init = function(model, req, res, id, Person) {
      return this.controller = new HomeController(this.model, this, id, Person);
    };

    return Show;

  })(app.View);

  HomeController = (function(_super) {
    __extends(HomeController, _super);

    function HomeController() {
      _ref4 = HomeController.__super__.constructor.apply(this, arguments);
      return _ref4;
    }

    HomeController.prototype.init = function(Post, View, id, Person) {
      Post.find(id);
      this.bind('click', '#person-login', function() {
        socket.disconnect();
        return Person.signin(function() {
          $('#user-email').val('');
          $('#user-password').val('');
          $('.modal').removeClass('active');
          $('.modal-bg').remove();
          return window.location.href = '{{{url}}}';
        });
      });
      this.bind('click', '#person-save', function() {
        socket.disconnect();
        return Person.signup(function() {
          $('#signup-email').val('');
          $('#signup-password').val('');
          $('.modal').removeClass('active');
          $('.modal-bg').remove();
          return window.location.href = '{{{url}}}';
        });
      });
      this.bind('click', '#signout_btn', function() {
        socket.disconnect();
        return Person.signout(function() {
          return window.location.href = '{{{url}}}';
        });
      });
      this.bind('click', '#post-save', function() {
        return Post.save(function() {
          $('#post-title').val('');
          $('#post-author').val('');
          $('#post-author_url').val('');
          $('#post-in_reply_to').val('');
          $('.modal').removeClass('active');
          return $('.modal-bg').remove();
        });
      });
      return this.connect('/post/save', function(req, res) {
        var _this = this;
        this.fullBody = '';
        req.on('data', function(chunk) {
          return _this.fullBody += chunk.toString();
        });
        return req.on('end', function() {
          _this.data = JSON.parse(_this.fullBody);
          Post.create(_this.data, res);
          return res.end('ok');
        });
      });
    };

    HomeController.prototype.render = function() {
      return this.view.render();
    };

    return HomeController;

  })(app.Controller);

  app.get('/', function(req, res) {
    this.post = new Post;
    this.person = new Person;
    return this.view = new Home(this.post, req, res, null, this.person);
  });

  app.get('/:id', function(req, res, id) {
    this.model = new Post;
    this.person = new Person;
    return this.view = new Show(this.post, req, res, id, this.person);
  });

}).call(this);
