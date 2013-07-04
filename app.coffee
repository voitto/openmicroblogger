
# Open Microblogger
# July 2, 2013


app = require( 'zygote' ).config
  port: 4444,
  dbname: 'omb2',
  dbuser: 'brian',
  dbpass: '',
  dbhost: 'localhost',
  dbport: 5432,
  url: 'http://localhost:4444'

class Post extends app.Model
  save: ( f ) -> # on the client-side, this POSTs the data
    $.ajax
      url: '/post/save'
      complete: f
      data: JSON.stringify
        title: $( '#post-title' ).val()
        author: $( '#post-author' ).val()
        author_url: $( '#post-author_url' ).val()
        in_reply_to: $( '#post-in_reply_to' ).val()

class Person extends app.Model
  signin: ( f ) ->
    $.ajax
      url: '/person/signin'
      complete: f
      data: JSON.stringify
        email: $( '#user-email' ).val()
        password: $( '#user-password' ).val()
  signup: ( f ) ->
    $.ajax
      url: '/person/signup'
      complete: f
      data: JSON.stringify
        email: $( '#signup-email' ).val()
        password: $( '#signup-password' ).val()
  signout: ( f ) ->
    $.ajax
      url: '/person/signout'
      complete: f
      data: JSON.stringify({})

class Home extends  app.View
  init: ( model, req, res, id, Person ) ->
    @controller = new HomeController @model, @, null, Person

class Show extends app.View
  init: ( model, req, res, id, Person ) ->
    @controller = new HomeController @model, @, id, Person

class HomeController extends app.Controller
  init: ( Post, View, id, Person ) ->
    Post.find( id )
    @bind 'click', '#person-login', ->
      socket.disconnect()
      Person.signin ->
        $( '#user-email' ).val ''
        $( '#user-password' ).val ''
        $( '.modal' ).removeClass 'active'
        $( '.modal-bg' ).remove()
        window.location.href = '{{{url}}}'
    @bind 'click', '#person-save', ->
      socket.disconnect()
      Person.signup ->
        $( '#signup-email' ).val ''
        $( '#signup-password' ).val ''
        $( '.modal' ).removeClass 'active'
        $( '.modal-bg' ).remove()
        window.location.href = '{{{url}}}'
    @bind 'click', '#signout_btn', ->
      socket.disconnect()
      Person.signout ->
        window.location.href = '{{{url}}}'
    @bind 'click', '#post-save', ->
      Post.save ->
        $( '#post-title' ).val ''
        $( '#post-author' ).val ''
        $( '#post-author_url' ).val ''
        $( '#post-in_reply_to' ).val ''
        $( '.modal' ).removeClass 'active'
        $( '.modal-bg' ).remove()
    @connect '/post/save', ( req, res ) -> # server: handle POST
      @fullBody = '';
      req.on 'data', (chunk) =>
        @fullBody += chunk.toString()
      req.on 'end', =>
        @data = JSON.parse @fullBody
        Post.create @data, res
        res.end 'ok'
  render: ->
    @view.render()

app.get '/', ( req, res ) ->
  @post = new Post
  @person = new Person
  @view = new Home @post, req, res, null, @person

app.get '/:id', ( req, res, id ) ->
  @model = new Post
  @person = new Person
  @view = new Show @post, req, res, id, @person











