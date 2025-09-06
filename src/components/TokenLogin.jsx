import { useParams } from "react-router-dom";
export default function TokenLogin(){
    const {token} = useParams()
   localStorage.setItem('token',token)
   window.location.href='/dashboard'
}