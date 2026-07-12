module Run.State
  ( State(..)
  , STATE
  , _state
  , liftState
  , liftStateAt
  , modify
  , modifyAt
  , put
  , putAt
  , get
  , getAt
  , gets
  , getsAt
  , runState
  , runStateAt
  , evalState
  , evalStateAt
  , execState
  , execStateAt
  ) where

import Prelude

import Data.Either (Either(..))
import Data.Symbol (class IsSymbol, reflectSymbol)
import Data.Tuple (Tuple(..), fst, snd)
import Prim.Row as Row
import Run (Run)
import Run as Run
import Control.Monad.Free (Free)
import Control.Monad.Free as Free
import Data.Functor.Variant (VariantF)
import Type.Proxy (Proxy(..))
import Type.Row (type (+))

data State s a = State (s -> s) (s -> a)

derive instance functorState :: Functor (State s)

type STATE s r = (state :: State s | r)

_state :: Proxy "state"
_state = Proxy

liftState :: forall s a r. State s a -> Run (STATE s + r) a
liftState = liftStateAt _state

liftStateAt
  :: forall q sym s a r
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> State s a
  -> Run r a
liftStateAt = Run.lift

modify :: forall s r. (s -> s) -> Run (STATE s + r) Unit
modify = modifyAt _state

modifyAt
  :: forall q sym s r
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> (s -> s)
  -> Run r Unit
modifyAt sym f = liftStateAt sym $ State f (const unit)

put :: forall s r. s -> Run (STATE s + r) Unit
put = putAt _state

putAt
  :: forall q sym s r
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> s
  -> Run r Unit
putAt sym = modifyAt sym <<< const

get :: forall s r. Run (STATE s + r) s
get = getAt _state

getAt
  :: forall q sym s r
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> Run r s
getAt sym = liftStateAt sym $ State identity identity

gets :: forall s t r. (s -> t) -> Run (STATE s + r) t
gets = getsAt _state

getsAt
  :: forall q sym s t r
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> (s -> t)
  -> Run r t
getsAt sym = flip map (getAt sym)

foreign import runStateAtImpl
  :: forall q s r a
   . Free.BindNodeClass
  -> Free.BindLeafClass
  -> Free.FreeObjClass
  -> (forall f x y. Free f x -> (x -> Free f y) -> Free f y)
  -> (forall rl x y. (x -> y) -> VariantF rl x -> VariantF rl y)
  -> (s -> a -> Tuple s a)
  -> String
  -> s
  -> Run r a
  -> Run q (Tuple s a)

runState :: forall s r a. s -> Run (STATE s + r) a -> Run r (Tuple s a)
runState = runStateAt _state

runStateAt
  :: forall q sym s r a
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> s
  -> Run r a
  -> Run q (Tuple s a)
runStateAt sym s r = runStateAtImpl
  Free.bindNodeClass
  Free.bindLeafClass
  Free.freeObjClass
  Free.bindImpl
  map
  Tuple
  (reflectSymbol sym) s r

evalState :: forall s r a. s -> Run (STATE s + r) a -> Run r a
evalState = evalStateAt _state

evalStateAt
  :: forall q sym s r a
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> s
  -> Run r a
  -> Run q a
evalStateAt sym s = map snd <<< runStateAt sym s

execState :: forall s r a. s -> Run (STATE s + r) a -> Run r s
execState = execStateAt _state

execStateAt
  :: forall q sym s r a
   . IsSymbol sym
  => Row.Cons sym (State s) q r
  => Proxy sym
  -> s
  -> Run r a
  -> Run q s
execStateAt sym s = map fst <<< runStateAt sym s
