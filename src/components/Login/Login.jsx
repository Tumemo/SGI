import BannerLogin from "../BannerLogin/BannerLogin"
import Formulario from "../Formulario/Formulario"

function Login(){
    return(
        <>
            <BannerLogin />
            <Formulario />
            <picture className="">
                <img src="/images/logo-sesi.png" alt="Logo do sesi" className="m-auto mt-6" />
            </picture>
        </>
        
    )
}

export default Login